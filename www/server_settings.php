<?php

// To use the plugin_log_file, insert something like this:
// INSERT INTO redcap_config (field_name,value) VALUES ('plugin_log_file', '/var/log/redcap/plugin_log.log') ON DUPLICATE KEY UPDATE value= '/var/log/redcap/plugin_log.log';

class Plugin {

	static $ts_start;


	/**
	 * A method of DB Query logging
	 * @param $sql
	 * @throws Exception
	 */
	public static function query_log($sql) {
		global $rc_connection, $plugin_log_file, $project_id;

		// Get the log path using plugin_log_file but with option to change file here
		if(PAGE == "DataEntry/search.php") {
			$logPath = dirname($plugin_log_file) . DS . "query_data_entry_search.log";
		} else {
			$logPath = dirname($plugin_log_file) . DS . "query.log";
		}


		// GET DATE WITH MICROSECONDS
		$t = microtime(true);
		$micro = sprintf("%06d",($t - floor($t)) * 1000000);
		$d = new DateTime( date('Y-m-d H:i:s.'.$micro, $t) );
		$date = $d->format("Y-m-d H:i:s.u"); // note at point on "u"

		// DETERMINE PROJECT ID
		if (empty($pid) && !empty($_GET['pid']))         $pid = $_GET['pid'];
		if (empty($pid) && !empty($project_id))          $pid = $project_id;
		if (empty($pid) && defined("PROJECT_ID")) $pid = PROJECT_ID;
		if (empty($pid))                                 $pid = "-";

		// DETERMINE USERNAME
		$username = defined('USERID') ? USERID : "";
		if (empty($username)) $username = "-";

		// MAX LEN FOR SQL
		$trim_length = 150;

		// TRIM SPACE AND CARRIAGE RETURNS
		$msg = trim(preg_replace('!\s+!', ' ', $sql));

		// TRIM REPETITIVE STATEMENTS
		if (strpos($msg,"REPLACE INTO redcap_sessions") !== false) {
			// Let's truncate these sql statements...
			$msg = trim(substr($msg,0, $trim_length));

			// APPEND ELLIPSES
			if (strlen($msg) >= $trim_length) $msg .= "...";
		}


		// GET ERROR INFO
		$error_no = mysqli_errno($rc_connection);
		$thread_id = mysqli_thread_id($rc_connection);

		// OTHER STUFF
		$other = "";
		if(PAGE == "api/index.php") {
			$other .= "\n\t\tPOST: " . substr(json_encode($_POST), 0, $trim_length);
		}

		if (PAGE == "Home/index.php") {
			$other .= "\n\t\tPOST: " . json_encode($_POST);
			$other .= "\n\t\tGET: " . json_encode($_GET);
			$other .= "\n\t\tREFER:" . json_encode($_SERVER['HTTP_REFERRER']);
		}

		if($error_no == 1062 || $error_no == 1064) {
			// BACKTRACE
			$e = new Exception();
			$bt = $e->getTraceAsString();

			// Get rid of first line
			$bta = explode("\n",$bt);
			array_shift($bta);
			$bt = implode("\n\t\t", $bta);
			// $bt = trim(preg_replace('!\s+!', ' ', $bt));
			$other .= "\n\t\t" . $bt; // substr($bt, 0, $trim_length * 2);
		}

		// WRITE LOG
		file_put_contents($logPath, "\n[" . $date . "]" .
			"\t" . $pid .
			"\t" . $username .
			"\t" . $thread_id .
			"\t" . $error_no .
			"\t" . PAGE .
			"\t" . $msg .
			$other
			, FILE_APPEND
		);
	}


	/**
	 * This method takes an optional first parameter as a string which is the filename to log to.
	 * It also optionally takes a last parameter as 'BT' or 'BT2' to include a backtrace
	 * @throws Exception
	 */
	public static function log2file() {
		global $project_id, $plugin_log_file;

		// Set the start time
		if (empty(self::$ts_start)) self::$ts_start = microtime(true);

		// Get all arguments
		$args = func_get_args();

		// Get the log path using plugin_log_file but with option to change file here
		$logPath = $plugin_log_file;
		$customFile = isset($args[0]) && is_string($args[0]) ? filter_var($args[0],FILTER_SANITIZE_STRING) : null;

		if (!empty($customFile)) {
			array_shift($args);
			$logPath = dirname($plugin_log_file) . DS . $customFile;
		}

		// BACKTRACE (remove one from this logging class)
		$bt = debug_backtrace();

		// GET A SIMPLE BACKTRACE AS WELL
		$e = new \Exception();
		$bt2 = $e->getTraceAsString();

		$function   = isset($bt[1]['function']) ? $bt[1]['function']    : "";
		$file       = isset($bt[0]['file'])     ? $bt[0]['file']        : "";
		$line       = isset($bt[0]['line'])     ? $bt[0]['line']        : "";

		// DETERMINE TIME
		$runtime = round ((microtime(true) - self::$ts_start) * 1000,1);

		// GET DATE WITH MICROSECONDS
		$t = microtime(true);
		$micro = sprintf("%06d",($t - floor($t)) * 1000000);
		$d = new DateTime( date('Y-m-d H:i:s.'.$micro, $t) );
		$date = $d->format("Y-m-d H:i:s.u"); // note at point on "u"


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
				$obj = "qgit swSTR";
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

			// Allow last argument to be a backtrace
			if ($i+1 == $count && $obj == "STR") {
				// Last argument
				$c = strtoupper($msg);
				if ($c == "BT" || $c == "BACKTRACE" ) {
					array_shift($bt);
					$msg = "\n\t" . str_replace("\n","\n\t", print_r($bt,true));
					$obj = "Backtrace";
				} elseif ($c == "BT2"|| $c == "BACKTRACE2") {
					// remove first line
					$bt2a = explode("\n", $bt2);
					array_shift($bt2a);
					$bt2 = implode("\n\t", $bt2a);
					$msg = "\n\t" . $bt2;
					$obj = "Backtrace2";
				}
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
		file_put_contents($logPath, $log,FILE_APPEND);
	}


	/**
	 * The original plugin log method...
	 */
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
		$runtime = sprintf("%.2f", (microtime(true) - self::$ts_start) * 1000);
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


	// A utility for inserting a tab into the page
	public static function injectPluginTabs($tab_href, $tab_name, $image_name = 'gear.png') {
		$msg = '<script>
		jQuery("#sub-nav ul li:last-child").before(\'<li class="active"><a style="font-size:13px;color:#393733;padding:4px 9px 7px 10px;" href="'.$tab_href.'"><img src="' . APP_PATH_IMAGES . $image_name . '" class="imgfix" style="height:16px;width:16px;"> ' . $tab_name . '</a></li>\');
		</script>';
		echo $msg;
	}


}

class StepTimer {

	public	$ts_start;
	public  $name;

	public  $steps = [];
	public  $lastStep;

	public function __construct($name)
	{
		// Start first step
		$this->ts_start = microtime(true);
		// Name of PerformanceDebugger
		$this->name = $name;
	}

	private function createStep($name) {
		$this->steps[$name] = [
			'stepCount' => 0,
			'stepDuration' => 0
		];
	}

	public function startStep($name) {
		if (!isset($this->steps[$name])) {
			// Create a placeholder
			$this->createStep($name);
		}

		// Start it
		$this->steps[$name]['startTime'] = microtime(true);
		$this->lastStep = $name;
	}

	public function endStep($name) {
		if (!isset($this->steps[$name])) {
			\Plugin::log("ERROR - ending step that wasn't created: " . $name);
			return false;
		}
		$endTime = microtime(true);
		$this->steps[$name]['stepDuration'] += $endTime - $this->steps[$name]['startTime'];
		$this->steps[$name]['stepCount'] ++;
		unset($this->steps[$name]['startTime']);
	}

	public function transition($nextStep) {
		if (!empty($this->lastStep)) {
			$this->endStep($this->lastStep);
		}
		$this->startStep($nextStep);
	}

	public function reset() {
		$this->steps = [];
	}

	public function getStatus() {
		return [
			$this->name => $this->steps
		];
	}
}
