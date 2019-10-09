<?php

// To use the plugin_log_file, insert something like this:
// INSERT INTO redcap_config (field_name,value) VALUES ('plugin_log_file', '/var/log/redcap/plugin_log.log') ON DUPLICATE KEY UPDATE value= '/var/log/redcap/plugin_log.log';

class Plugin {

    static $ts_start;

    public static function log() {

        global $project_id, $plugin_log_file;

        // Set the start time
        if (empty(self::$ts_start)) self::$ts_start = microtime(true);

        // Get all arguments
        $args = func_get_args();

        // BACKTRACE (remove one from this logging class)
        $bt = debug_backtrace();

        /*  In cases where you call this log function from a parent object's log function, you really are interested
            in the backtrace from one level higher.  To make the logic work, we strip off the last backtrace array
            element.  If, on the other hand, you simply instantiate this and call it from a script, you will not need
            to strip.  There is a caveat - if you wrap your script with a function called log/debug/error it may
            erroneously strip a backtrace and leave your line number and calling function incorrect.

            To summarize, if you wrap this in a parent object, call it with methods 'log,debug, or error' and everything
            should work.
        */

        // // If you do not specify, we 'guess' if we should fix the backtrace by looking at the name of the function 1 level up
        // if (isset($bt[1]["function"])
        //     && in_array($bt[1]['function'], array("log","debug","error","emLog","emDebug","emError"))
        //     && is_null($fix_backtrace)) $fix_backtrace = true;
        //
        // // PARSE BACKTRACE
        // if ($fix_backtrace) array_shift($bt);

        $function   = isset($bt[1]['function']) ? $bt[1]['function']    : "";
        $file       = isset($bt[0]['file'])     ? $bt[0]['file']        : "";
        $line       = isset($bt[0]['line'])     ? $bt[0]['line']        : "";

        // DETERMINE TIME
        $runtime = round ((microtime(true) - self::$ts_start) * 1000,1);
        $date = date('Y-m-d H:i:s');

        // DETERMINE PROJECT ID
        $pid        = isset($_GET['pid'])       ? $_GET['pid']          : (empty($project_id) ? "-" : $project_id);

        // DETERMINE USERNAME
        $username = defined('USERID') ? USERID : "";
        if (empty($username)) $username = "-";

        // Convert into an array in the event someone passes a string or other variable type
        if (!is_array($args)) $args = array($args);


        $count = count($args);
        $entries = array();
        foreach ($args as $i => $message) {

            // Convert arrays/objects into string for logging
            if (is_array($message)) {
                $obj = "ARR";
                $msg = empty($message) ? "()" : print_r($message, true);
            } elseif (is_object($message)) {
                $obj = "OBJ";
                $msg = print_r($message, true);
            } elseif (is_string($message) || is_numeric($message)) {
                $obj = "STR";
                $msg = $message;
            } elseif (is_bool($message)) {
                $obj = "BOOL";
                $msg = ($message ? "true" : "false");
            } elseif (is_null($message)) {
                $obj = "NULL";
                $msg = "";
            } else {
                $obj = "UNK";
                $msg = print_r($message, true);
            }

            $entry = array(
                "date"     => $date,
                "ms"       => $runtime,
                "pid"      => $pid,
                "username" => $username,
                "file"     => basename($file, '.php'),
                "line"     => $line,
                "function" => $function,
                "arg"      => "[" . ($i + 1) . "/" . $count . "]",
                "obj"      => $obj,
                "msg"      => $msg
            );
            $entries[] = implode("\t", $entry);
        } // loop

        $log = implode("\n", $entries) . "\n";

        // Output to plugin log if defined, else use error_log
        if (!empty($plugin_log_file)) {
            file_put_contents($plugin_log_file, $log,FILE_APPEND);
        }
    }







/*

    public static function log($message, $type = 'INFO', $prefix = '') {
        global $project_id, $plugin_log_file;

        // SET DEFAULTS FOR ALL PROJECTS
        $default_debug_level = 2;               // 2 = ALL, 1= INFO+ERROR, 0 = ERROR ONLY
        $default_use_error_log = false;

        $override = array(
            '7829' => array('debug_level' => 2, 'use_error_log' => false)
        );

        $debug_level = ( isset($override[$project_id]['debug_level']) ? $override[$project_id]['debug_level'] : $default_debug_level );
        $use_error_log = ( isset($override[$project_id]['use_error_log']) ? $override[$project_id]['use_error_log'] : $default_use_error_log );

        if ($type == 'ERROR' || ($debug_level == 1 && $type == 'INFO') || $debug_level > 1) {
            // Get calling file using php backtrace to help label where the log entry is coming from
            $bt = debug_backtrace();
            $calling_file = $bt[0]['file'];
            $calling_line = $bt[0]['line'];
            $calling_function = $bt[3]['function'];
            if (empty($calling_function)) $calling_function = $bt[2]['function'];
            if (empty($calling_function)) $calling_function = $bt[1]['function'];
//            if (empty($calling_function)) $calling_function = $bt[0]['function'];

            // Convert arrays/objects into string for logging
            if (is_array($message)) {
                $msg = "(array): " . print_r($message,true);
            } elseif (is_object($message)) {
                $msg = "(object): " . print_r($message,true);
            } elseif (is_string($message) || is_numeric($message)) {
                $msg = $message;
            } elseif (is_bool($message)) {
                $msg = "(boolean): " . ($message ? "true" : "false");
            } else {
                $msg = "(unknown): " . print_r($message,true);
            }

            // Prepend prefix
            if ($prefix) $msg = "[$prefix] " . $msg;

            // Build log row
            $output = array(
                date( 'Y-m-d H:i:s' ),
                empty($project_id) ? "-" : $project_id,
                basename($calling_file, '.php'),
                $calling_line,
                $calling_function,
                $type,
                $msg
            );

            // Output to plugin log if defined, else use error_log
            if (!empty($plugin_log_file)) {
                file_put_contents(
                    $plugin_log_file,
                        implode("\t",$output) . "\n",
                    FILE_APPEND
                );
            }
            if ($use_error_log) {
                // Output to error log
                error_log(implode("\t",$output));
            }

            // Output to screen
            if ($debug_level == 3) {
                print "<pre style='background: #eee; border: 1px solid #ccc; padding: 5px;'>";
                print "Type: $type\n";
                print "File: ".basename($calling_file, '.php')."\n";
                if ($calling_function) print "Func: $calling_function\n";
                print "Msg : $msg</pre>";
            }
        }
    }
*/


}