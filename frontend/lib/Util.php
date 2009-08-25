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
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: $url");
		echo "This resource may be found at <a href=\"$url\">$url</a>.";
		exit();
	}
	
	// Redirects the user to the login page
	static function login() {
		Util::redirect("login.php?redirect=" . urlencode(Util::current_url()));
	}
	
	// ---------------------------------------------------------------------
	// Base url, etc.
	// ---------------------------------------------------------------------
	
	static function current_url() {
		//$_SERVER["PATH_INFO"]
		// TODO: strip base url?
		return $_SERVER['REQUEST_URI'];
	}
	
	static function base_url() {
		$pathinfo = pathinfo($_SERVER["SCRIPT_NAME"]);
		$base = 'http://' . $_SERVER["SERVER_NAME"] . $pathinfo['dirname'];
		if (substr($base,-1) != '/') $base .= '/';
		return $base;
	}
	
	// ---------------------------------------------------------------------
	// Files and directories
	// ---------------------------------------------------------------------
	
	// Is a file sourcecode?
	static function is_code($filename) {
	}
	
	// Create a new (temporary) directory
	static function temporary_name($parent, $prefix = '') {
		// TODO: should we make this path relative?
		return tempnam($parent, $prefix);
	}
	static function create_new_directory($parent, $prefix = '') {
		$tempfile = Util::temporary_name($parent, $prefix);
		if (file_exists($tempfile)) {
			unlink($tempfile);
		}
		mkdir($tempfile);
		return $tempfile;
	}
}
