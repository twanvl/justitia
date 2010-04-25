<?php

// -----------------------------------------------------------------------------
// Temporary directory wrapper
// -----------------------------------------------------------------------------

class Tempdir {
	public $dir;
	
	function __construct($in,$name) {
		$this->dir = SystemUtil::temporary_directory($in,$name);
	}
	function __destruct() {
		SystemUtil::delete_directory($this->dir);
	}
	
	function file($name) {
		return $this->dir . '/' . $name;
	}
}
