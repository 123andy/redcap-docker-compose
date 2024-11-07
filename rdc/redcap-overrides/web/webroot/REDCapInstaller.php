<?php

/**
 * Class RI - REDCap Installer
 */
class REDCapInstaller {

    // Database Config
    private $hostname,
            $db,
            $username,
            $password,
            $salt;

    public $errors = array();       // Place to record error alerts
    public $successes = array();    // Place to record success alerts
    public $debug = array();        // place to dump debug output

    public $redcap_webroot_path;    // This is the path relative to the web root where REDCap will be run (default is '/').
    public $redcap_webroot_url;     // The url to the webroot
    public $redcap_webroot_url_internal; // The url of the web root from the web server's perspective

    public $install_path;           // The full file path where REDCap is being installed

    public $db_conn;                // DB Connection

    public $step = 1;               // Install Step (1 = zip file, 2 = database setup)


    /**
     *  Parse the POST if present
     */
    public function __construct() {
        try {

            // INITIALIZE DB FROM ENV PARAMS
            $this->hostname = 'db';
            $this->db = empty($_ENV['MYSQL_DATABASE']) ? FALSE : $_ENV['MYSQL_DATABASE'];
            $this->username = empty($_ENV['MYSQL_USER']) ? FALSE : $_ENV['MYSQL_USER'];
            $this->password = empty($_ENV['MYSQL_PASSWORD']) ? FALSE : $_ENV['MYSQL_PASSWORD'];
            $this->salt = empty($_ENV['REDCAP_SALT']) ? '12345678' : $_ENV['REDCAP_SALT'];

            // GET THE INSTALL PATH FROM THE .ENV...
            $this->redcap_webroot_path = (empty($_ENV['REDCAP_WEBROOT_PATH'])) ? '/' : $_ENV['REDCAP_WEBROOT_PATH'];

            $this->redcap_webroot_url = $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['SERVER_NAME'] .
                (($_ENV['WEB_PORT'] == "80") ? "" : ":" . $_ENV['WEB_PORT']) .
                $this->redcap_webroot_path;

            // ...but inside the container we neither need nor want the port number.
            $this->redcap_webroot_url_internal = $_SERVER['REQUEST_SCHEME'] . "://" . "localhost" . $this->redcap_webroot_path;

            // INCLUDE REDCAP CONNECT!
            if (file_exists("." . $this->redcap_webroot_path . "redcap_connect.php")) {
                include_once "." . $this->redcap_webroot_path . "redcap_connect.php";
            }

            if ($_SERVER['REQUEST_METHOD'] == 'POST') {

                $init_table = empty($_POST['init-table']) ? false : true;
                $this->install_path = __DIR__ . $this->redcap_webroot_path;
                $install_option = empty($_POST['install-option']) ? false : $_POST['install-option'];

                //            $install_folder = empty($_POST['install-folder']) ? "redcap" : $_POST['install-folder'];
                //            $this->install_path = __DIR__ . DIRECTORY_SEPARATOR . ($install_folder == "base" ? "" : $install_folder . DIRECTORY_SEPARATOR);

                switch ($install_option) {
                    case "upload":
                        $zip_path = $this->handleUpload('installer-upload');
                        break;
                    case "consortium":
                        $zip_path = $this->downloadInstallerFromConsortium();
                        break;
                    default:
                        throw new RuntimeException("Unknown/missing install option ($install_option)");
                }

                if ($zip_path !== false && empty($this->errors)) {

                    // Continue unzipping
                    $dest_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "redcap-installer";
                    $result = $this->unzipFile($zip_path, $dest_path);

                    //                $this->successes[] = "Zip Path: " . $zip_path;
                    //                $this->successes[] = "Dest Path: " . $dest_path;
                    //                $this->successes[] = "Install Path: " . $this->install_path;

                    if (! is_dir($this->install_path)) shell_exec("mkdir -p " . $this->install_path);

                    if ($result == true && empty($this->errors)) {
                        // SUCCESS
                        $this->successes[] = "REDCap successfully unzipped to $dest_path";

                        // MOVE UNZIPPED FILES TO WEBROOT
                        shell_exec("mv " . $dest_path . "/redcap/* " . $this->install_path);

                        // CLEAN UP FILES
                        //                    exec(sprintf("rm -rf %s", escapeshellarg($dest_path)));
                        //                    unlink($zip_path);

                        // TODO - what should happen here is to refresh this page and include the redcap_connect so we can

                        // TODO - Add users automatically to this dev instance since setting up table-based users is such a PITA.

                        // CREATE DATABASE.PHP
                        $this->buildDatabaseDotPhp($this->install_path);

                        // SETUP DATABASE
                        if (! $this->initializeDatabase()) throw new RuntimeException("Unable to initialize Database.  Proceed with manual database setup.");

                        // TEST IF DATABASE IS ALREADY SET UP
                        $q = $this->db_query("SHOW TABLES LIKE 'redcap%'");
                        $redcap_tables = mysqli_num_rows($q);
                        if ($redcap_tables > 0) throw new RuntimeException("There are $redcap_tables existing tables" .
                                " starting with 'redcap' in their name in the " . $this->db . " database." .
                                " This script will halt automatic table creation of a new REDCap database.<br><br>" .
                                " If you wish to delete the existing database and start new, you need to remove the docker volume" .
                                " that contains the database files.  The name of this volumne will contain <strong>" .
                                $_ENV['MYSQL_DIR'] . "</strong> and can be found by executing this command on your terminal:" .
                                " <code>docker volume ls</code>.  You can then remove the volume with <code>docker volume rm" .
                                " XXX</code> where XXX is the name of the volume <i>e.g. rdc_mysql-volume</i>");

                        // GET THE INSTALL SQL
                        $install_url_internal = $this->redcap_webroot_url_internal . "install.php";
                        $install_url = $this->redcap_webroot_url . "install.php";
                        $sql = file_get_contents($install_url_internal . "?sql=1");
                        if (empty($sql)) throw new RuntimeException("Unable to obtain installation SQL from $install_url_internal which is exposed to you as $install_url");

                        // RUN THE INSTALL SQL
                        $commands = 0;
                        if (mysqli_multi_query($this->db_conn, $sql)) do {
                            // Nothing needed here
                            $commands++;
                        } while (mysqli_more_results($this->db_conn) && mysqli_next_result($this->db_conn));
                        $this->successes[] = "Install SQL Script Completed $commands queries";

                        // TEST IF WE NOW HAVE TABLES
                        $q = $this->db_query("SHOW TABLES LIKE 'redcap%'");
                        $redcap_tables = mysqli_num_rows($q);
                        if ($redcap_tables == 0) throw new RuntimeException("Install SQL did not appear to work.  Check database and do manual setup.");

                        // Set the REDCap Base URL to circumvent errors in install.php step 3 when the port
                        // is not the default for the protocol
                        $q = $this->db_query("UPDATE redcap_config set value = '$this->redcap_webroot_url' where field_name='redcap_base_url'");

                        $redcap_version = $this->db_query("SELECT value FROM `redcap_config` WHERE field_name='redcap_version'")
                        ->fetch_assoc()['value'];

                        // Set the Login Image
                        $q = $this->db_query("UPDATE redcap_config set value = '/RDC_LOGO.png' where field_name='login_logo'");

                        // Set the reporting mode to 0 (do not report stats to VUMC)
                        $q = $this->db_query("UPDATE redcap_config set value = '0' where field_name='auto_report_stats'");
                        $this->successes[] = "Turn off auto-reporting of statistics (turn back on if you want)";
                        
                        // Set server as development server
                        $q = $this->db_query("UPDATE redcap_config set value = '1' where field_name='is_development_server'");
                        $this->successes[] = "Set server type as development server";
                        
                        // Set autologout to never
                        $q = $this->db_query("UPDATE redcap_config set value = '0' where field_name='autologout_timer'");
                        $this->successes[] = "Set server logout time to never (to reduce headaches for local development)";
                        
                        // Direct the user to the remainder of the REDCap install.php
                        $this->successes[] = "Installed $redcap_tables REDCap tables to " . $this->db . " on " . $this->hostname;
                        $nextUrl = $this->redcap_webroot_url . "redcap_v" . $redcap_version . "/ControlCenter/check.php";
                        $this->successes[] = "<h5>Initial setup complete!</h5>" . 
                            //  "You should <strong>SKIP step 1</strong> on" .
                            //    " the next page as this script has already created your database structures.<br>Simply press 'Save Changes'" .
                            //    " and move onto the next steps." .
                            // <br>Click <a href='" . $install_url . "'>$install_url</a>. to continue." . 
                            "<br>Click <a href='$nextUrl'>$nextUrl</a> to continue.";

                        if ($init_table) {

                            $init_table_email = ($_POST['init-table-email']) ?: "you@example.org";
                            $defaultUsers = $this->createDefaultUsersArray($init_table_email);
                            $users_added = "";
                            foreach($defaultUsers as $user) {
                                $result = $this->createUser(...$user);
                                $users_added .= $user[0] . "\n";
                            }

                            if ( version_compare($redcap_version, '10.1.0', '>=') ) {
                                $admin_sql = "UPDATE redcap_user_information SET
                                    access_system_config=1,
                                    access_system_upgrade=1,
                                    access_external_module_install=1,
                                    admin_rights=1,
                                    access_admin_dashboards=1
                                        WHERE username='admin'";
                                $this->db_query($admin_sql);
                            }

                            $this->successes[] = "Created users: $users_added";

                            // Turn on table-based auth
                            $this->db_query("UPDATE redcap_config SET value = 'table' WHERE field_name = 'auth_meth_global'");
                            // revoke site_admin's admin privs to avoid warning
                            $this->db_query("UPDATE redcap_user_information SET super_user = 0 WHERE username = 'site_admin'");
                        }

                        $this->db_conn->close();
                        $this->step = 99; // DONE
                    }
                }
            }
        } catch (RuntimeException $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
    }


    public function createUser($username, $email, $first_name, $last_name, $password, $super = 0, $account_manager = 0, $salt_string = 'my_salt_string') {
        $sql = sprintf("insert into redcap_user_information " .
                "(username, user_email, user_firstname, user_lastname, super_user, user_creation, account_manager) values " .
                "('%s', '%s', '%s', '%s', %s, NOW(), %s)", $username, $email, $first_name, $last_name, $super, $account_manager);
        if (!$this->db_query($sql)) return;
        // REDCap will automatically create a new salt and corresponding hashed password for users marked as using legacy_hash (i.e. md5)
        $sql = sprintf("insert into redcap_auth " .
                "(username, password, password_salt, legacy_hash, temp_pwd, password_question, password_answer, password_question_reminder, password_reset_key) values " .
                "('%s', md5(concat('%s', '%s')), '%s', 1, 0, NULL, NULL, NULL, NULL)", $username, $password, $salt_string, $salt_string);
        $result = $this->db_query($sql);
        return $result;
    }

    private function createDefaultUsersArray($email = "you@example.org") {
        // Create 5 default users with a variable email address
        $admin = [
            /* $username = */ 'admin',
            /* $email = */ $email,
            /* $first_name = */ 'joe',
            /* $last_name = */ 'admin',
            /* $password = */ 'password',
            /* $super = */ 1,
            /* $account_manager = */ 0,
            /* $salt_string = */ 'my_salt_string'
        ];

        $alice = [
            /* $username = */ 'alice',
            /* $email = */ $email,
            /* $first_name = */ 'alice',
            /* $last_name = */ 'manager',
            /* $password = */ 'password',
            /* $super = */ 0,
            /* $account_manager = */ 1,
            /* $salt_string = */ 'my_salt_string'
        ];

        $bob = [
            /* $username = */ 'bob', /* $email = */ $email, /* $first_name = */ 'bob', /* $last_name = */ 'user', /* $password = */ 'password', /* $super = */ 0, /* $account_manager = */ 0, /* $salt_string = */ 'my_salt_string'
        ];

        $carol = [
            /* $username = */ 'carol', /* $email = */ $email, /* $first_name = */ 'carol', /* $last_name = */ 'user', /* $password = */ 'password', /* $super = */ 0, /* $account_manager = */ 0, /* $salt_string = */ 'my_salt_string'
        ];

        $dan = [
            /* $username = */ 'dan', /* $email = */ $email, /* $first_name = */ 'dan', /* $last_name = */ 'user', /* $password = */ 'password', /* $super = */ 0, /* $account_manager = */ 0, /* $salt_string = */ 'my_salt_string'
        ];


        return [$admin, $alice, $bob, $carol, $dan];
    }


    /**
     * Build a database.php file to the dest_path
     * @param  string $dest_path
     * @return bool
     */
    public function buildDatabaseDotPhp($dest_path) {
        try {

            if (empty($this->hostname)) throw new RuntimeException("Missing required hostname");
            if (empty($this->db)) throw new RuntimeException("Missing required db");
            if (empty($this->username)) throw new RuntimeException("Missing required username");
            if (empty($this->password)) throw new RuntimeException("Missing required password");
            if (empty($this->salt)) throw new RuntimeException("Missing required salt");

            $contents = array();
            $contents[] = '<?php';
            $contents[] = 'global $log_all_errors;';
            $contents[] = '$log_all_errors = FALSE;';
            $contents[] = '$hostname = "' . $this->hostname . '";';
            $contents[] = '$db       = "' . $this->db       . '";';
            $contents[] = '$username = "' . $this->username . '";';
            $contents[] = '$password = "' . $this->password . '";';
            $contents[] = '$salt     = "' . $this->salt     . '";';
            $contents[] = '';

            file_put_contents($dest_path . "database.php", implode("\n\t",$contents));
            return true;
        } catch (RuntimeException $e) {
            $this->errors[] = $e->getMessage() . " in " . __FUNCTION__;
            return false;
        }
    }


    /**
     * Get the available versions as an unauthenticated get query
     * [
     *    "lts" => [
     *       [ "version_number" => "1.2.3", "release_date" => "", "release_notes" => "" ],
     *   ],
     *   "std" => [
     *      [ "version_number" => "1.2.3", "release_date" => "", "release_notes" => "" ],
     * ]
     * @return bool|string
     */
    public function getInstallerVersions(){
        try {
            $result = file_get_contents("https://redcap.vanderbilt.edu/plugins/redcap_consortium/versions.php");
            $results = json_decode($result,true);
            $opt_groups = array();
            foreach ($results as $branch => $versions) {
                $options = array();
                if (!is_array($versions)) continue;
                foreach ($versions as $version) {
                    $val = $branch . "--" . $version['version_number'];
                    $options[] = "<option value='$val'>v" .
                        $version['version_number'] .
                        " Released " . $version['release_date'] .
                        (empty($version['release_notes']) ? "" : " (" . $version['release_notes'] . ")" ) .
                        "</option>";
                }
                // Add them in reverse order so STD goes before LTS
                array_unshift($opt_groups,"<optgroup label='$branch'>" . implode("", $options) . "</optgroup>");
            }
            $result = implode("",$opt_groups);
        } catch (RuntimeException $e) {
            $this->errors[] = $e->getMessage() . " in " . __FUNCTION__;
            $result = false;
        }
        return $result;
    }



    /**
     * Wrapper for running a query
     * @param $sql
     * @return bool|mysqli_result
     */
    public function db_query($sql) {
        $q = mysqli_query($this->db_conn, $sql);
        return $q;
    }


    /**
     * Test database.php connection file
     * @param $db_connect_file
     * @return bool
     */
    public function initializeDatabase() {
        try {
            //Connect to db
            $this->db_conn = new mysqli($this->hostname, $this->username, $this->password, $this->db);

            if ($this->db_conn->connect_errno) {
                throw new RuntimeException("Username ($this->username) / password (XXXXXX) could not connect to $this->db at $this->hostname.  Check your database.php file");
            }

            $result = true;
        } catch (RuntimeException $e) {
            $this->errors[] = $e->getMessage() . " in " . __FUNCTION__;
            $result = false;
        }
        return $result;
    }


    /**
     * Download installer from consortium as authenticated user
     * @param $version
     * @return bool|string
     */
    public function downloadInstallerFromConsortium() {
        try {
            // VALIDATE
            if (empty($_POST['username'])) throw new RuntimeException('Missing required username');
            if (empty($_POST['password'])) throw new RuntimeException('Missing required password');
            if (empty($_POST['version'])) throw new RuntimeException('Missing required version');
            list($branch,$version) = explode("--", $_POST['version']);
            if (empty($version)) throw new RuntimeException('Missing required version');

            $url = 'https://redcap.vanderbilt.edu/plugins/redcap_consortium/versions.php';
            $postdata = http_build_query(array(
                        'username' => $_POST['username'],
                        'password' => $_POST['password'],
                        'version' => $version,
                        'install' => 1
                        ));

            // Make and open dest file
            $dest_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "redcap" . $version . ".zip";
            $fp = fopen($dest_path, 'w+');

            // Pull File
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $server_output = curl_exec($ch);
            curl_close($ch);
            fclose($fp);

            if ($server_output != 1) {
                throw new RuntimeException("Error with curl download");
            }

            // Check filesize
            $size = filesize($dest_path);
            //echo "<br>FileSize:<pre>" . print_r($server_output,true)."</pre>";
            if ($size < 10000000) {
                // Incomplete download - see if we can parse a json message from the result
                $contents = file_get_contents($dest_path);
                $data = @json_decode($contents, true);
                if ($data === null
                        && json_last_error() !== JSON_ERROR_NONE) {
                    throw new RuntimeException("Unknown issue with file download $dest_path");
                } else {
                    if (!empty($data['ERROR'])) throw new RuntimeException($data['ERROR']);
                }
            }
            $result = $dest_path;
        } catch (RuntimeException $e) {
            $this->errors[] = $e->getMessage() . " in " . __FUNCTION__;
            $result = false;
        }
        return $result;
    }


    /**
     * MANUAL FILE UPLOAD
     *
     * $_FILES= [
     *     "installer-file" => [
     *         "name" => "Meslo LG M Regular for Powerline.ttf",
     *         "type" => "application/octet-stream",
     *         "tmp_name" => "/tmp/phpaCthNn",
     *         "error" => 0,
     *         "size" => 475628
     *     ]
     * ]
     */
    public function handleUpload($field_name, $strip_redcap_folder = TRUE) {
        try {
            // Undefined | Multiple Files | $_FILES Corruption Attack
            // If this request falls under any of them, treat it invalid.
            if (
                    !isset($_FILES[$field_name]['error']) ||
                    is_array($_FILES[$field_name]['error'])
               ) {
                throw new RuntimeException('Invalid parameters.');
            }

            // Check $_FILES['upfile']['error'] value.
            switch ($_FILES[$field_name]['error']) {
                case UPLOAD_ERR_OK:
                    break;
                case UPLOAD_ERR_NO_FILE:
                    throw new RuntimeException('No file sent.');
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    throw new RuntimeException('Exceeded filesize limit.');
                default:
                    throw new RuntimeException('Unknown errors.');
            }

            // As of 8.7.3, size is 26475806
            if ($_FILES[$field_name]['size'] > 70000000) {
                throw new RuntimeException('Exceeded filesize limit.');
            }

            // DO NOT TRUST $_FILES['upfile']['mime'] VALUE !!
            // Check MIME Type by yourself.
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            if (false === $ext = array_search(
                        $finfo->file($_FILES[$field_name]['tmp_name']),
                        array(
                            'zip' => 'application/zip'
                            ),
                        true
                        )) {
                throw new RuntimeException('Invalid file format.');
            }

            // DO NOT USE $_FILES['upfile']['name'] WITHOUT ANY VALIDATION !!
            // Generate a same temp file - also will persist across session
            $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "redcap-upload-" . sprintf('%s.%s', sha1_file($_FILES[$field_name]['tmp_name']), $ext);
            if (! move_uploaded_file( $_FILES[$field_name]['tmp_name'], $path ) ) {
                throw new RuntimeException('Failed to move uploaded file.');
            }
            return $path;
        } catch (RuntimeException $e) {
            $this->errors[] = $e->getMessage() . " in " . __FUNCTION__;
            return false;
        }
    }


    /**
     * UNZIP THE ARCHIVE
     *
     * @param      $source_path
     * @param bool $strip_redcap_folder
     * @return mixed    FALSE if failure, otherwise PATH to unzipped contents
     */

    public function unzipFile($source_path, $dest_path) {
        try {
            // UNZIP IT
            $zip = new ZipArchive;
            $res = $zip->open($source_path);

            if ($res === TRUE) {
                $zip->extractTo($dest_path);
                $zip->close();
            } else {
                throw new RuntimeException('Failed to unzip source file:' + $source_path);
            }

            return true;
        } catch (RuntimeException $e) {
            $this->errors[] = $e->getMessage() . " in " . __FUNCTION__;
            return false;
        }
    }


    /**
     * Build Bootstrap Alerts
     * @param        $alerts
     * @param string $style
     */
    public function displayAlerts($alerts, $style="alert-danger") {
        if (!empty($alerts)) {
            $output = array();
            foreach ($alerts as $alert) {
                $output[] = "<div class='alert $style alert-dismissible mt-1 fade show'>" . $alert .
                    "<button type='button' class='close' data-dismiss='alert'><span aria-hidden='true'>&times;</span></button>" .
                    "</div>";
            }
            echo implode("\n", $output);
        }
    }


    /**
     * Render Page Header
     */
    public function displayPageHeader() {
        ?>
            <html>
            <head>
            <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
            <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.4.1/css/all.css" integrity="sha384-5sAR7xN1Nv6T6+dT2mhtzEpVJvfS3NScPQTrOxhwjIuvcA67KV2R5Jz6kr4abQsz" crossorigin="anonymous">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/css/select2.min.css">
            <script src="https://code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
            <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.bundle.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/js/select2.min.js"></script>
            <link href="https://fonts.googleapis.com/css?family=Source+Serif+Pro" rel="stylesheet">
            <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro" rel="stylesheet">
            <!--    Boostrap 4 for select 2 from https://raw.githubusercontent.com/ttskch/select2-bootstrap4-theme/master/dist/select2-bootstrap4.min.css-->
            <style>
            .select2-container--bootstrap4 .select2-selection--single{height:calc(2.25rem + 2px)!important}.select2-container--bootstrap4 .select2-selection--single .select2-selection__placeholder{color:#757575;line-height:2.25rem}.select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow{position:absolute;top:50%;right:3px;width:20px}.select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow b{top:60%;border-color:#343a40 transparent transparent;border-style:solid;border-width:5px 4px 0;width:0;height:0;left:50%;margin-left:-4px;margin-top:-2px;position:absolute}.select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered{line-height:2.25rem}.select2-search--dropdown .select2-search__field{border:1px solid #ced4da;border-radius:.25rem}.select2-results__message{color:#6c757d}.select2-container--bootstrap4 .select2-selection--multiple{min-height:calc(2.25rem + 2px)!important}.select2-container--bootstrap4 .select2-selection--multiple .select2-selection__rendered{-webkit-box-sizing:border-box;box-sizing:border-box;list-style:none;margin:0;padding:0 5px;width:100%}.select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice{color:#343a40;border:1px solid #bdc6d0;border-radius:.2rem;padding:0;padding-right:5px;cursor:pointer;float:left;margin-top:.3em;margin-right:5px}.select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__remove{color:#bdc6d0;font-weight:700;margin-left:3px;margin-right:1px;padding-right:3px;padding-left:3px;float:left}.select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__remove:hover{color:#343a40}.select2-container :focus{outline:0}.select2-container--bootstrap4 .select2-selection{border:1px solid #ced4da;border-radius:.25rem;width:100%}.select2-container--bootstrap4.select2-container--focus .select2-selection{border-color:#17a2b8;-webkit-box-shadow:0 0 0 .2rem rgba(0,123,255,.25);box-shadow:0 0 0 .2rem rgba(0,123,255,.25)}.select2-container--bootstrap4.select2-container--focus.select2-container--open .select2-selection{border-bottom:none;border-bottom-left-radius:0;border-bottom-right-radius:0}select.is-invalid~.select2-container--bootstrap4 .select2-selection{border-color:#dc3545}select.is-valid~.select2-container--bootstrap4 .select2-selection{border-color:#28a745}.select2-container--bootstrap4 .select2-dropdown{border-color:#ced4da;border-top:none;border-top-left-radius:0;border-top-right-radius:0}.select2-container--bootstrap4 .select2-dropdown .select2-results__option[aria-selected=true]{background-color:#e9ecef}.select2-container--bootstrap4 .select2-results__option--highlighted,.select2-container--bootstrap4 .select2-results__option--highlighted.select2-results__option[aria-selected=true]{background-color:#007bff;color:#f8f9fa}.select2-container--bootstrap4 .select2-results__option[role=group]{padding:0}.select2-container--bootstrap4 .select2-results__group{padding:6px;display:list-item;color:#6c757d}.select2-container--bootstrap4 .select2-selection__clear{width:1.2em;height:1.2em;line-height:1.15em;padding-left:.3em;margin-top:.5em;border-radius:100%;background-color:#6c757d;color:#f8f9fa;float:right;margin-right:.3em}.select2-container--bootstrap4 .select2-selection__clear:hover{background-color:#343a40}

        /** SPINNER CREATION **/
        .loader {
position: relative;
          text-align: center;
margin: 15px auto 35px auto;
        z-index: 9999;
display: block;
width: 80px;
height: 80px;
border: 10px solid rgba(0, 0, 0, .3);
        border-radius: 50%;
        border-top-color: #000;
animation: spin 1s ease-in-out infinite;
           -webkit-animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin {
            to {
                -webkit-transform: rotate(360deg);
            }
        }
        @-webkit-keyframes spin {
            to {
                -webkit-transform: rotate(360deg);
            }
        }

        /** MODAL STYLING **/
        .modal-content {
            border-radius: 0;
            box-shadow: 0 0 20px 8px rgba(0, 0, 0, 0.7);
        }

        .loader-txt {
            p {
                font-size: 13px;
color: #666;
       small {
           font-size: 11.5px;
color: #999;
       }
            }
        }

        </style>

            <!--    Custom CSS-->
            <style>
            * {
                font-family: 'Source Sans Pro', sans-serif;
                /*font-family: 'Source Serif Pro', serif;*/
            }
        .bg-cardinal {background-color: #8c1515}
        .install-option {display:none;}
        .card-footer {display:none;}
        .supplement-options {display:none;}
        </style>
            </head>
            <body>
            <?php
    }


} // End of class
