<?php

// -----------------------------------------------------------------------------
// Utility functions
// -----------------------------------------------------------------------------

class Util {
	// ---------------------------------------------------------------------
	// Redirect
	// ---------------------------------------------------------------------
	
	// Redirects the user to $url.
	static function redirect($url) {
		if ($url == '') {
			$url = 'index.php';
		}
		if ($url{0} != '/') $url = Util::base_url() . $url;
		//header("HTTP/1.1 301 Moved Permanently");
		header("HTTP/1.1 302 Found");
		header("Location: $url");
		echo "This resource may be found at <a href=\"$url\">$url</a>.";
		exit();
	}
	
	// ---------------------------------------------------------------------
	// Base url, etc.
	// ---------------------------------------------------------------------
	
	static function current_url() {
		$script = basename($_SERVER['SCRIPT_NAME']);
		return $script . @$_SERVER['PATH_INFO'];
	}
	
	static function current_script_is($script) {
		return strpos($_SERVER['SCRIPT_NAME'],$script) !== false;
	}
	
	static function base_url() {
		$dirname = pathinfo($_SERVER["SCRIPT_NAME"], PATHINFO_DIRNAME);
		$server = $_SERVER["SERVER_NAME"];
		if ($_SERVER['SERVER_PORT'] != 80) {
			$server .= ':' . $_SERVER['SERVER_PORT'];
		}
		$base = 'http://' . $server . $dirname;
		if (substr($base,-1) != '/') $base .= '/';
		return $base;
	}
	
	// ---------------------------------------------------------------------
	// Content type
	// ---------------------------------------------------------------------
	
	static function is_code($filename) {
		return Language::by_filename($filename)->is_code;
	}
	static function content_type($filename) {
		$ext = pathinfo($filename, PATHINFO_EXTENSION);
		if ($ext == 'diff') {
			// TODO: determine whether this is a diff in HTML format
			return 'text/html';
		} else if ($ext == 'in' || $ext == 'out' || $ext == 'diff' || $ext == 'err') {
			return 'text/plain';
		} else if (Util::is_code($filename)) {
			return 'text/plain';
		} else {
			return 'application/octet-stream';
		}
	}
}
