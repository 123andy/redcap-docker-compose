<?php

// SCAN YOUR REDCAP TO SEE IF THERE ARE UPDATES TO BE HAD FORM GIT



// Find all folders that have a .gitsubrepo folder

$doUpdate = isset($argv[1]) && $argv[1] == "true";

$dirs = scanDir::scan(__DIR__, ".git", true);



// $files = array_slice($files,0,2); // DEBUG TEMP
// echo print_r($dirs);

// Parse out subrepo information
$subrepos = array();
$current = array();
$behind = array();
$other = array();
$errors = array();


$update = array();

foreach ($dirs as $i => $dir) {
    echo "Checking [$i] $dir...";

    $result = syscall("git fetch", $dir);
    $result = syscall("git status", $dir);

    if (!empty($result['error'])) {
        $errors[] = $result;
        $status = "errors";
    } elseif (strpos($result['output'], "Your branch is up to date with") !== false && strpos($result['output'], "working tree clean") !== false) {
        // echo "\n$dir is current";
        $current[] = $dir;
        $status = "current";
    } elseif (strpos($result['output'], "Your branch is behind") !== false) {
        $behind[] = $dir;
        $update[] = "cd $dir; git pull;";
        $status = "behind";
    } else {
        $other[$dir] = $result;
        $status = "see details";
    }

    echo $status . "\n";


    // // Open file
    // $subrepo = $this->parseSubRepoFile($file);
    // if (empty($subrepo)) continue;
    //
    // // Get path relative to repo root and full path
    // $subrepo['relative_path'] = str_replace($this->repo_path . DIRECTORY_SEPARATOR, "", dirname($file));
    // $subrepo['full_path'] = $file;
    // // $subrepo['remote_name'] = basename($subrepo['remote']);
    //
    // // Add to list
    // $subrepos[] = $subrepo;
}

if (!empty($errors)) {
    echo "\n============= ERRORS =============\n";
    echo print_r($errors);
    echo "\n";
}

if (!empty($other)) {
    echo "\n============= OTHER ISSUES =============\n";
    foreach ($other as $dir => $detail) {
        // echo json_encode($o) . "\n";
        echo $dir . "\n";
    }
}



if (!empty($current)) {
    echo "\n============= CURRENT =============\n";
    echo count($current) . " of " . count($dirs) . " are current\n";
}

if (!empty($behind)) {
    echo "\n============= BEHIND =============\n";
    echo count($behind) . " of " . count($dirs) . " are behind\n";
    echo "\t" . implode("\n\t", $behind);

    if ($doUpdate) {
        echo "Pulling updates...\n";
        foreach ($behind as $dir) {
            $result = syscall("git pull", $dir);
            echo "\n----------------\n" . $dir . "\n" . print_r($result,true) . "\n";
        }
        echo "\n";
    } else {
        echo "\n\nExecute the following to update these repos or re-run this script with a 'true' argument (e.g. php " . $argv[0] . " true)\n" . implode("\n", $update) . "\n";
    }

}


// var_dump($errors);
// var_dump($current);
// var_dump($behind);
// var_dump($other);



// $this->log($files);
// $this->log($subrepos);
// $this->subrepos = $subrepos;





/**
 * Utility function for callls ing command-line functions as the www user
 * @param $cmd string                   command to call
 * @param $cwd string                   current working directory (defaults to repo)
 * @param $minimal_success_log boolean  if command is successful - log minimally
 * @param $useBash boolean              Load bash shell
 * @return boolean true/false           if command was successful
 */
function syscall ($cmd, $cwd, $useBash = false)
{
    if ($useBash === true) {
        // We will be passing all input through the www-data user's bash
        $bashCmd        = $cmd;
        $cmd            = 'bash';
        $descriptorspec = array(
            0 => array('pipe', 'r'), // stdin
            1 => array('pipe', 'w'), // stdout is a pipe that the child will write to
            2 => array('pipe', 'w')  // stderr out
        );
    } else {
        $descriptorspec = array(
            1 => array('pipe', 'w'), // stdout is a pipe that the child will write to
            2 => array('pipe', 'w')  // stderr out
        );
    }

    $resource = proc_open($cmd, $descriptorspec, $pipes, $cwd);

    if (is_resource($resource)) {
        // if ($useBash) {
        //     fwrite($pipes[0], escapeshellcmd('source ' . $_ENV['APACHE_RUN_HOME'] . DIRECTORY_SEPARATOR . '.bash_profile')."\n");
        //     fwrite($pipes[0], escapeshellcmd($bashCmd) . "\n");
        //     fclose($pipes[0]);
        // }

        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($resource);
    } else {
        $error = "Unable to open resource";
    }

    $result = array (
        "cmd" => $cmd,
        "cwd" => $cwd,
        "output" => $output,
        "error" => $error);

    return $result;
}


class scanDir {
    static private $directories, $files, $ext_filter, $recursive, $dir_filter;

    // ----------------------------------------------------------------------------------------------
    // scan(dirpath::string|array, extensions::string|array, recursive::true|false,  skipDirs::array)
    static public function scan(){
        // Initialize defaults
        self::$recursive = true;
        self::$directories = array();
        self::$files = array();
        self::$ext_filter = false;
        self::$dir_filter = array();

        // Check we have minimum parameters
        if(!$args = func_get_args()){
            die("Must provide a path string or array of path strings");
        }
        if(gettype($args[0]) != "string" && gettype($args[0]) != "array"){
            die("Must provide a path string or array of path strings");
        }

        // Check if recursive scan | default action: no sub-directories
        if(isset($args[2]) && $args[2] == true){self::$recursive = true;}

        // Check if recursive scan | default action: no sub-directories
        if(isset($args[3])) {
            if(gettype($args[3]) == "array"){
                self::$dir_filter = $args[3];
            } else if(gettype($args[3]) == "string"){
                self::$dir_filter[] = $args[3];
            }
        }

        // Was a filter on file extensions included? | default action: return all file types
        if(isset($args[1])){
            if(gettype($args[1]) == "array"){self::$ext_filter = array_map('strtolower', $args[1]);}
            else
                if(gettype($args[1]) == "string"){self::$ext_filter[] = strtolower($args[1]);}
        }

        // Grab path(s)
        self::verifyPaths($args[0]);
        // return self::$files;

        // Remove Duplicates and Re-index
        return array_values(array_unique(self::$files));
    }

    static private function verifyPaths($paths){
        $path_errors = array();
        if(gettype($paths) == "string"){$paths = array($paths);}

        foreach($paths as $path){
            if(is_dir($path)){
                self::$directories[] = $path;
                $dirContents = self::find_contents($path);
            } else {
                $path_errors[] = $path;
            }
        }
    }

    // This is how we scan directories
    static private function find_contents($dir){

        // Skip filtered directories
        foreach (self::$dir_filter as $filter) {
            if (strpos($dir, $filter) !== false) {
                // echo "<br>Skipping [$dir]";
                return false;
            } else {
                // echo "<br>Including [$dir]";
            }
        }

        $result = array();
        $root = scandir($dir);
        // self::$files[] = $result[] = "Scanning $dir";
        foreach($root as $value){
            // echo "<br>START $value";
            if($value === '.' || $value === '..') {continue;}
            if(is_link($value)) {continue;}


            // SKIPPING NORMAL BEHAVIOR HERE
            if(is_file($dir.DIRECTORY_SEPARATOR.$value)){
                if(!self::$ext_filter || in_array(strtolower(pathinfo($dir.DIRECTORY_SEPARATOR.$value, PATHINFO_EXTENSION)), self::$ext_filter)){
                    self::$files[] = $result[] = $dir.DIRECTORY_SEPARATOR.$value;
                    // echo "<br>Adding $dir / $value";
                }
                continue;
            }


            // GET DIR for .GIT
            if ($value == ".git") {
                self::$files[] = $result[] = $dir;
                continue;
            }

            // // Check for dotfile matches
            // if(!self::$ext_filter || ( (substr($value,0,1) === ".") && in_array(substr($value,1), self::$ext_filter))) {
            //     self::$files[] = $result[] = $dir.DIRECTORY_SEPARATOR.$value;
            //     echo "<br>Adding $dir / $value";
            //     continue;
            // }

            if (self::$recursive) {
                foreach(self::find_contents($dir.DIRECTORY_SEPARATOR.$value) as $foo) {
                    // echo "<br>Adding2 $dir / $value";
                    self::$files[] = $result[] = $foo;
                }
            }
        }
        // Return required for recursive search
        return $result;
    }
}



