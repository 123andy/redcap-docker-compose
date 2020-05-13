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
}