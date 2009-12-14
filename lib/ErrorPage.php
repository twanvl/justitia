<?php

class ErrorPage extends Template {
	function __construct($message) {
		$this->message = $message;
	}
	function title() {
		return "Error";
	}
	function write_body() {
		echo $this->message;
		echo "\n<br><a href='javascript:history.back();'>back</a>";
	}
	
	// Die with a fancy error message
	static function die_fancy($message) {
		// Utility: error pages
		$view = new ErrorPage($message);
		$view->write();
		exit();
	}
}
