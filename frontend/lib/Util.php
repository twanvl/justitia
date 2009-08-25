<?php

// -----------------------------------------------------------------------------
// Utility functions
// -----------------------------------------------------------------------------

class Util {
	// Redirects the user to $url.
	static function redirect($url) {
		if ($url == '') {
			$url = 'index.php';
		}
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: $url");
		echo "This resource may be found at <a href=\"$url\">$url</a>.";
		exit();
	}
	
	static function current_url() {
		//$_SERVER["PATH_INFO"]
		// TODO: strip base url?
		return $_SERVER['REQUEST_URI'];
	}
	
	// Redirects the user to the login page
	static function login() {
		Util::redirect("login.php?redirect=" . urlencode(Util::current_url()));
	}
}
