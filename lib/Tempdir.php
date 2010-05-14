<?php

// -----------------------------------------------------------------------------
// Temporary directory wrapper
// -----------------------------------------------------------------------------

class Tempdir {
	public $dir;
	private $delete;
	
	function __construct($in,$name) {
		$this->dir = SystemUtil::temporary_directory($in,$name);
		$this->delete = true;
	}
	function __destruct() {
		if ($this->delete) {
			SystemUtil::delete_directory($this->dir);
		}
	}
	
	function file($name) {
		return $this->dir . '/' . $name;
	}
	function create_parent_dirs($name) {
		@mkdir(dirname($this->file($name)), 0777, true);
	}
	function get_and_keep() {
		$this->delete = false;
		return $this->dir;
	}
}
