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
		return tempnam($parent, $prefix);
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
	
	// Run a shell command more conveniently
	function run_command($cmd,$args) {
		// windows fix
		if (SystemUtil::is_windows()) {
			$cmd = str_replace('/',"\\",$cmd);
		}
		// build command line
		$command = escapeshellcmd($cmd);
		foreach($args as $arg) {
			$command .= ' ' . escapeshellarg($arg);
		}
		if (!file_exists($cmd)) {
			throw new Exception("Command not found: $cmd");
		}
		// execute
		system($command, $retval);
		return $retval == 0;
	}
	
	// Run a shell command in a safe way
	function safe_command($cmd,$args,$limits=array()) {
		// TODO make this actually safe!!!
		return SystemUtil::run_command($cmd,$args);
	}
	
}
