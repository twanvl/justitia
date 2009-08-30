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
	
	function build_command($cmd,$args) {
		// windows fix
		if (SystemUtil::is_windows()) {
			$cmd = str_replace('/',"\\",$cmd);
		}
		// only run existing files?
		if (!file_exists($cmd)) {
			throw new Exception("Command not found: $cmd");
		}
		// build command line
		$command = escapeshellcmd($cmd);
		foreach($args as $arg) {
			$command .= ' ' . escapeshellarg($arg);
		}
		return $command;
	}
	
	// Run a shell command more conveniently
	function run_command($cmd,$args) {
		// execute
		system(SystemUtil::build_command($cmd,$args), $retval);
		return $retval == 0;
	}
	
	// Run a shell command with given stdion, return array(retval,stdout,stderr)
	function run_command_io($cmd,$args, $stdin,  $workingdir = NULL, $env = NULL) {
		$command = SystemUtil::build_command($cmd,$args);
		$descriptorspec = array(
			0 => array("pipe", "r"), // stdin
			1 => array("pipe", "w"), // stdout
			2 => array("file", "w")  // stderr
		);
		// start
		$process = proc_open($command, $descriptorspec, $pipes, $workingdir, $env);
		if (!is_resource($process)) {
			return array(false,"","Failed to run command $cmd");
		}
		// handle io
		$stdout = '';
		$stderr = '';
		while (true) {
			$to_write = array($pipes[0]);
			$to_read  = array($pipes[1], $pipes[2]);
			$to_error = NULL;
			// wait for at most 0.2 seconds
			$num_changed_streams = stream_select($to_read,$to_write,$to_error,0,200000);
			if ($num_changed_streams === false) {
				// TODO: handle error
			} else {
				// write stdin
				if (in_array($pipes[0],$to_write)) SystemUtil::write_stream($pipes[0],$stdin);
				// read stdout
				if (in_array($pipes[1],$to_write)) SystemUtil::read_stream($pipes[1],$stdout);
				// read stderr
				if (in_array($pipes[2],$to_write)) SystemUtil::read_stream($pipes[2],$stderr);
			}
		}
		// done
		fclose($pipes[1]);
		$return_value = proc_close($process);
		return array($return_value,$stdout,$stderr);

	}
	
	private function write_stream($fp, &$str) {
		if ($str == '') return;
		$written = fwrite($fp,substr($str,0,8192));
		$str = substr($str, $written);
	}
	private function read_stream($fp, &$str) {
		$read = fread($fp,8192);
		$str .= $read;
	}
	
	// Run a shell command in a safe way
	function safe_command($cmd,$args,$limits=array()) {
		// TODO make this actually safe!!!
		return SystemUtil::run_command($cmd,$args);
	}
	
}
