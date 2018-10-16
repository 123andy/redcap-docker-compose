<?php

/**
 * This is the REDCap Installer Page
 *
 * It helps you upload and install REDCap on a clean web server
 */

set_time_limit ( 600 ); // 10 Minute Execution Time


/**
 * Class RI - REDCap Installer
 */
class REDCapInstaller {

// Database
private $hostname,
    $db,
    $username,
    $password,
    $salt;


public $errors = array();       // Place to record error alerts
public $successes = array();    // Place to record success alerts
public $debug = array();        // place to dump debug output

public $install_path;           // The path where REDCap is being installed

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


        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $install_option = empty($_POST['install-option']) ? false : $_POST['install-option'];
            $install_folder = empty($_POST['install-folder']) ? "redcap" : $_POST['install-folder'];
            $this->install_path = __DIR__ . DIRECTORY_SEPARATOR . ($install_folder == "base" ? "" : $install_folder . DIRECTORY_SEPARATOR);

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
                $result = $this->unzipFile($zip_path, $install_folder == "base");

                if ($result == true && empty($this->errors)) {
                    // SUCCESS
                    $this->successes[] = "REDCap successfully unzipped to $this->install_path";

                    // TODO - what should happen here is to refresh this page and include the redcap_connect so we can
                    // just use all the redcap logic to complete the setup....  Saving that for another day...

                    // TODO - Add users automatically to this dev instance since setting up table-based users is such a PITA.

                    // CREATE DATABASE.PHP
                    $dest_path = $this->install_path . "database.php";
                    $this->buildDatabaseDotPhp($dest_path);

                    // SETUP DATABASE
                    if (! $this->initializeDatabase()) throw new RuntimeException("Unable to initialize Database.  Proceed with manual database setup.");

                    // TEST IF DATABASE IS ALREADY SET UP
                    $q = $this->db_query("SHOW TABLES LIKE 'redcap%'");
                    $redcap_tables = mysqli_num_rows($q);
                    if ($redcap_tables > 0) throw new RuntimeException("There are $redcap_tables redcap* tables in the " . $this->db . " database.  Please update manually.");

                    // GET THE INSTALL SQL
                    $install_url = "http://localhost/" . ($install_folder == "base" ? "" : $install_folder . "/") . "install.php";
                    $sql = file_get_contents($install_url . "?sql=1");
                    if (empty($sql)) throw new RuntimeException("Unable to obtain installation SQL from $install_url");

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

                    $this->successes[] = "Installed $redcap_tables REDCap tables to " . $this->db . " on " . $this->hostname;
                    $this->successes[] = "Resume setup at <a href='" . $install_url . "'>$install_url</a>";
                    $this->db_conn->close();
                }
            }
        }
    } catch (RuntimeException $e) {
        $this->errors[] = $e->getMessage();
        return false;
    }
}


//TODO
public function createUser($username, $email, $first_name, $last_name, $password, $super = 0) {
    $sql = sprintf("insert into redcap_user_information " .
        "(username, user_email, user_firstname, user_lastname, super_user, user_creation) values " .
        "('%s', '%s', '%s', '%s', %i, NOW())", $username, $email, $first_name, $last_name, $super);
    $result = $this->db_query($sql);
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

        file_put_contents($dest_path, implode("\n\t",$contents));
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
            if (empty($versions)) continue;
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
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
        if ($_FILES[$field_name]['size'] > 50000000) {
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
 * @param      $path
 * @param bool $strip_redcap_folder
 * @return bool
 */
public function unzipFile($path, $strip_redcap_folder = FALSE) {
    try {
        // UNZIP IT
        $zip = new ZipArchive;
        $res = $zip->open($path);

        if ($res === TRUE) {
            //echo "<br>Opened Zip file at $path";
            if ($strip_redcap_folder) {
                // REMOVE THE /redcap FOLDER FROM THE ARCHIVE
                $subfolder_to_extract = 'redcap';
                $count = 0;

                // Loop through archive
                for ($i = 0; $i < 3; $i++) {
                //for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);

                    // $count will only be 1 if zip file starts with $subfolder_to_extract
                    $filename2 = preg_replace('/^' . $subfolder_to_extract . '\//', "", $filename, 1, $count);
                    if ($count == 1) {
                        $isDir = (substr($filename2, -1, 1) == '/');
                        $dest_file = $this->install_path . $filename2;

                        // If source item in zip is a directory, check if it exists
                        // filename2 is empty for the 'base' directory after $subfolder_to_extract is removed
                        if ($isDir || empty($filename2)) {
                            if (!is_dir($dest_file)) {
                                mkdir($dest_file, 0777, true);
                            }
                        } else {
                            // Zip item is a file
                            // Make sure required destination directory exists
                            $dest_path = pathinfo($dest_file);
                            if (!file_exists($dest_path['dirname'])) {
                                mkdir($dest_path['dirname'], 0777, true);
                            }

                            // Extract file
                            copy("zip://" . $path . "#" . $filename, $dest_file);
                        }
                    }
                }
            } else {
                // Simply unzip to the directory root
                $zip->extractTo(__DIR__);
            }
            $zip->close();
        } else {
            throw new RuntimeException('Failed to unzip file');
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
    </style>
</head>
<body>
<?php
}


} // End of class



$RI = new REDCapInstaller();
$RI->displayPageHeader();

?>
    <div id="main-navbar" class="navbar navbar-dark bg-dark navbar-fixed-top">
        <a class="navbar-brand" href="/">
            <img style="height: 75px;" src="https://www.ctsi.ufl.edu/files/2017/07/REDCap-App-Icon.png">
        </a>
        <h1 class="text-light">REDCap Docker-Compose Installer</h1>
        <small class="text-light">By <a class="text-light" href="mailto:andy123@stanford.edu">Andy Martin</a></small>
    </div>

    <div class="container">
<?php

$RI->displayAlerts($RI->successes, "alert-success");
$RI->displayAlerts($RI->errors);

if ($RI->step == 1) {
    // STARTING STEP
    ?>
    <form id="form-upload" enctype="multipart/form-data" class="form" method="POST">
        <div class="mt-2 card install">
            <div class="card-header bg-cardinal text-light">
                <h3><i class="fas fa-dice-one"></i> The REDCap Installer ZIP</h3>
            </div>
            <div class="card-body">
                <div>
                    <h5>
                        You can install REDCap on this docker server using either of two methods:
                    </h5>
                    <div class="form-check">
                        <label class="form-check-label">
                            <input type="radio" value="consortium" class="form-check-input" name="dl-option">Use your
                            <a target="_blank" href="https://community.projectredcap.org">Community Consortium</a> login
                            to download the latest version automatically
                        </label>
                    </div>
                    <div class="form-check">
                        <label class="form-check-label">
                            <input type="radio" value="upload" class="form-check-input" name="dl-option">Upload a full
                            .zip installer (perhaps provided by your consortium representative)
                        </label>
                    </div>
                </div>

                <div class="install-option option-consortium mt-2 p-2 border border-secondary rounded">
                    <h5>Enter REDCap Consortium Credentials:</h5>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" class="form-control" name="username"
                               placeholder="Consortium username (e.g. andy.martin)">
                        <small id="username-help" class="form-text text-muted">Look above your profile image for your
                            username, typically it is firstname.lastname
                        </small>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" class="form-control" name="password" placeholder="Password">
                    </div>
                    <div class="form-group">
                        <label for="password"><h6>Select REDCap Version</h6></label>
                        <div>
                            <select name="version"><?php echo $RI->getInstallerVersions() ?></select>
                        </div>
                        <small class="form-text text-muted">Select the version of REDCap to install</small>
                    </div>
                </div>

                <div class="install-option option-upload mt-2 p-2 border border-secondary rounded">
                    <h5>Upload redcap_vx.y.z.zip Installer:</h5>
                    <div class="input-group mb-3">
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="installer-upload" name="installer-upload">
                            <label class="custom-file-label" for="installer-upload">Choose file</label>
                        </div>
                    </div>
                </div>

                <div class="install-option option-folder mt-2 p-2 border border-secondary rounded">
                    <h5>Select how to install REDCap on your webserver:</h5>
                    <div class="form-check">
                        <label class="form-check-label">
                            <input type="radio" value="redcap" class="form-check-input" name="install-folder"
                                   checked="checked">
                            By default, REDCap will be installed in the redcap folder, so your web url will be <a
                                    target="_blank" href="http://localhost/redcap">http://localhost/redcap</a>
                        </label>
                        <small class="form-text text-muted">This configuration can be useful if you wish to install
                            other services on this web server (such as multiple REDCap versions)
                        </small>
                    </div>
                    <div class="form-check">
                        <label class="form-check-label">
                            <input type="radio" value="base" class="form-check-input" name="install-folder">You can also
                            installed into the 'root' of your web folder, so your web url will be
                            <a target="_blank" href="http://localhost">http://localhost</a>
                        </label>
                        <small class="form-text text-muted">This most likely resembles your production deployment and
                            some find it 'prettier' than the subfolder default
                        </small>
                    </div>
                </div>

            </div>
            <div class="card-footer">
                <input type="hidden" name="install-option"/>
                <button type="submit"
                        class="input-group btn btn-lg btn-success initiate-installation text-center d-block">INSTALL
                    REDCAP
                </button>
                <small class="form-text text-muted">Any existing files will be overwritten. This make time some time to
                    download and extract... Be patient!
                </small>
            </div>
        </div>
    </form>
    <?php
} elseif ($RI->step == 2) {
    // DATABASE SETUP TBD
    ?>
    <?php
    }

// END OF PAGE
?>
</div>


<!-- Modal -->
<div class="modal fade" id="loading" tabindex="-1" role="dialog" aria-labelledby="loadingLabel">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-body text-center">
                <div class="loader"></div>
                <div class="loader-txt">
                    <p>Building your REDCap Server</p>
                    <small>This can take a few minutes...</small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>

    $(document).ready(function(){

        // Handle upload option
        $('input[name="dl-option"]').bind('click', function() {
            var option = $(this).val();

            // Save selected option
            $('input[name="install-option"]').val(option);

            // Show parameters
            $('div.install-option').hide();
            $('div.install-option.option-folder').fadeIn();
            $('div.option-' + option).fadeIn();
        });


        // Update the bootstrap file uploader
        $('input[type="file"]').change(function(e){
            var fileName = e.target.files[0].name;
            $('.custom-file-label').html(fileName);
        });


        // Handle Loader
        $('button.initiate-installation').click(function(evt){
            evt.preventDefault();

            $("#loading").modal({
                backdrop: "static",     //remove ability to close modal with click
                keyboard: false,        //remove option to close with keyboard
                show: true              //Display loader!
            });

            setTimeout( function(){
                $('#form-upload').submit();
            }, 100 );
        });


        // Create a select2 box for the versions
        $('select[name="version"]')
            .css({"width":"100%"})
            .select2({
                'theme': 'bootstrap4'
            });

    });

</script>



<pre>
    <?php
        // DEBUGGING INFO
        foreach ($RI->debug as $k => $v) {
            echo "\n\n" . $k ."\n". print_r($v,true);
        }
    ?>
</pre>


</body>
</html>
