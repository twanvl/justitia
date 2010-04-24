<?php

// -----------------------------------------------------------------------------
// System / filesystem utilities
// -----------------------------------------------------------------------------

class SystemUtil {
	
	// ---------------------------------------------------------------------
	// Files and directories
	// ---------------------------------------------------------------------
	
	// Create a new (temporary) directory
	static function temporary_name($parent, $prefix = '') {
		// TODO: should we make this path relative?
		$name = tempnam($parent, $prefix);
		$name = str_replace("\\","/",$name); // (windows fix) backslashes in paths are a bad idea
		return $name;
	}
	static function temporary_directory($parent, $prefix = '') {
		$tempfile = SystemUtil::temporary_name($parent, $prefix);
		if (file_exists($tempfile)) {
			unlink($tempfile);
		}
		mkdir($tempfile);
		return $tempfile;
	}
	function delete_directory($dir) {
		if (!file_exists($dir)) return true;
		if (!is_dir($dir) || is_link($dir)) return unlink($dir);
		foreach (scandir($dir) as $item) {
			if ($item == '.' || $item == '..') continue;
			$itemname = $dir . "/" . $item;
			if (!SystemUtil::delete_directory($itemname)) {
				chmod($itemname, 0777);
				if (!SystemUtil::delete_directory($itemname)) return false;
			};
		}
		return rmdir($dir);
	}
	
	// ---------------------------------------------------------------------
	// Operating system
	// ---------------------------------------------------------------------
	
	function is_windows() {
		return strpos(php_uname('s'),'indows') !== false;
	}
	
	// ---------------------------------------------------------------------
	// System commands
	// ---------------------------------------------------------------------
	
	function build_command($cmd,$args, $error_out = NULL) {
		// windows fix
		if (SystemUtil::is_windows()) {
			$cmd = str_replace('/',"\\",$cmd);
		}
		// only run existing files?
		if (!file_exists($cmd)) {
			throw new InternalException("Command not found: $cmd");
		}
		// build command line
		$command = escapeshellcmd($cmd);
		foreach($args as $arg) {
			$command .= ' ' . escapeshellarg($arg);
		}
		if ($error_out) {
			if (SystemUtil::is_windows()) {
				$command .= " >$error_out";
			} else {
				$command .= " &>$error_out";
			}
		}
		return $command;
	}
	
	// Run a shell command more conveniently
	function run_command($working_dir,$cmd,$args, $error_out = NULL) {
		// change dir and execute
		$previous_dir = getcwd();
		$command = SystemUtil::build_command($cmd,$args,$error_out);
		if ($working_dir) chdir($working_dir);
		system($command, $retval);
		chdir($previous_dir);
		return $retval == 0;
	}
	
	private function write_stream(&$fp, &$str) {
		if (!$fp) return;
		if ($str == '') {
			fclose($fp);
			$fp = false;
			return;
		}
		$written = fwrite($fp,substr($str,0,8192));
		$str = substr($str, $written);
		echo "WRITTEN: $written\n";
	}
	private function read_stream($fp, &$str) {
		if (!$fp) return;
		$read = fread($fp,8192);
		$str .= $read;
		echo "READ: $read len:" . strlen($read), "\n";
	}
	
	// Run a shell command in a safe way
	function safe_command($working_dir,$cmd,$args, $limits, $error_out = NULL) {
		// not on windows
		if (SystemUtil::is_windows()) {
			echo "Security Warning: Runguard doesn't work on windows!\n";
			return SystemUtil::run_command($working_dir,$cmd,$args, $error_out);
		}
		// build command
		$actual_cmd  = RUNGUARD_PATH;
		$actual_args = array();
		// time limit
		$actual_args []= "--time=" . (isset($limits['time limit']) ? $limits['time limit'] : 60);
		// memory limit
		if (isset($limits['memory limit'])) $actual_args []= "--memsize=" . intval($limits['memory limit'] / 1024);
		// filesize limit
		if (isset($limits['filesize limit'])) $actual_args []= "--filesize=" . intval($limits['filesize limit'] / 1024);
		// process limit
		if (isset($limits['process limit'])) $actual_args []= "--nproc=" . $limits['process limit'];
		// no coredumps
		$actual_args []= "--no-core";
		// user
		if (isset($limits['as nobody']) && RUNGUARD_USER !== false) $actual_args []= "--user=" . RUNGUARD_USER;
		// run
		$actual_args []= $cmd;
		$actual_args = array_merge($actual_args,$args);
		return SystemUtil::run_command($working_dir,$actual_cmd,$actual_args, $error_out);
	}
	
}
