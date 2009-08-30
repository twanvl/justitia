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
			2 => array("pipe", "w")  // stderr
		);
		// start
		$process = proc_open($command, $descriptorspec, $pipes, $workingdir, $env);
		if (!is_resource($process)) {
			return array(false,"","Failed to run command $cmd");
		}
		// nonblocking
		foreach ($pipes as $p) stream_set_blocking($p,0);
		// handle io
		$stdout = '';
		$stderr = '';
		while (true) {
			// status
			// poll
			$to_write = array(); $to_read = array();
			if ($pipes[0]) $to_write []= $pipes[0];
			if ($pipes[1]) $to_read  []= $pipes[1];
			if ($pipes[2]) $to_read  []= $pipes[2];
			$to_error = NULL;
			// wait for at most 0.1 seconds
			$num_changed_streams = stream_select($to_read,$to_write,$to_error,0,100000);
			echo "----\n";
			var_dump($num_changed_streams);
			var_dump($to_read);
			var_dump($to_write);
			if ($num_changed_streams === false) {
				// TODO: handle error
				echo "error";
			} else if ($num_changed_streams == 0) {
				$status = proc_get_status($process);
				if (!$status['running']) break;
			} else {
				// write stdin
				if (in_array($pipes[0],$to_write)) SystemUtil::write_stream($pipes[0],$stdin);
				// read stdout
				if (in_array($pipes[1],$to_read))  SystemUtil::read_stream($pipes[1],$stdout);
				// read stderr
				if (in_array($pipes[2],$to_read))  SystemUtil::read_stream($pipes[2],$stderr);
			}
		}
		// done
		if ($pipes[0]) fclose($pipes[0]);
		if ($pipes[1]) fclose($pipes[1]);
		if ($pipes[2]) fclose($pipes[2]);
		$return_value = proc_close($process);
		return array($return_value,$stdout,$stderr);

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
	function safe_command($cmd,$args, $limits) {
		$actual_cmd  = RUNGUARD_PATH;
		$actual_args = array();
		// time limit
		$actual_args []= "--time=" . (isset($limits['time limit']) ? $limits['time limit'] : 60);
		// memory limit
		if (isset($limits['memory limit'])) $actual_args []= "--memsize=" . $limits['memory limit'];
		// filesize limit
		if (isset($limits['filesize limit'])) $actual_args []= "--filesize=" . $limits['filesize limit'];
		// no coredumps
		$actual_args []= "--no-core";
		// user
		if (RUNGUARD_USER !== false) $actual_args []= "--user=" . RUNGUARD_USER;
		// run
		$actual_args []= $cmd;
		$actual_args = array_merge($actual_args,$args);
		return SystemUtil::run_command($actual_cmd,$actual_args);
	}
	
}
