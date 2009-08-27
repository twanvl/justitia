<?php

class Log {
	// Log an error
	static function error($msg) {
		static $logging = false;
		if ($logging) return; // prevent a possible loop
		$logging = true;
		Log::do_log($msg);
		$logging = false;
	}
	private static function do_log($msg) {
		echo "<big><pre>$msg</pre></big>";
	}
}