<?php

class ErrorPage extends Template {
	private $except;
	
	function __construct($except) {
		$this->except = $except;
		if ($this->except instanceof NotFoundException) {
			header('x',true,404); // set http status code tot 404 Not Found
		}
	}
	function title() {
		return "Error";
	}
	function write_body() {
		echo '<div class="error-message">';
		echo htmlspecialchars( $this->except->getMessage() );
		if (method_exists($this->except,'getDetails')) {
			echo "<br>", $this->except->getDetails();
		}
		echo '</div>';
		echo "\n<br><a href='javascript:history.back();'>back</a>";
		// details
		$this->write_block_begin("Error details",'block collapsable collapsed');
		echo "<pre>";
		echo htmlspecialchars($this->except->getTraceAsString());
		echo "</pre>";
		$this->write_block_end();
	}
	
	// Die with a fancy error message
	static function die_fancy($except) {
		// Utility: error pages
		$view = new ErrorPage($except);
		$view->write();
		exit();
	}
}
