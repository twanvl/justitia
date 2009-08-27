<?php

// -----------------------------------------------------------------------------
// Programming languages
// -----------------------------------------------------------------------------

global $languages, $languages_by_extension;
$languages = array(
	'c' => array(
		'is_language'		=> true,
		'name'			=> 'c',
		'filename_regex'	=> '.*\.c'
	),
	'c++' => array(
		'is_language'		=> true,
		'name'			=> 'c++',
		'filename_regex'	=> '.*\.(c++|cc|cxx)'
	),
	'java' => array(
		'is_language'		=> true,
		'name'			=> 'java',
		'filename_regex'	=> '.*\.(java)'
	),
	'haskell' => array(
		'is_language'		=> true,
		'name'			=> 'haskell',
		'filename_regex'	=> '.*\.(hs|lhs)'
	),
	'any' => array(
		'is_language'		=> false,
		'name'			=> 'any',
		'filename_regex'	=> '.*'
	),
	'unknown' => array(
		'is_language'		=> false,
		'name'			=> 'unknown',
		'filename_regex'	=> ''
	)
);
$languages['']    = $languages['any'];
$languages['cpp'] = $languages['c++'];

// extension -> language
$languages_by_extension = array(
	'c'    => $languages['c'],
	'cc'   => $languages['c++'],
	'cxx'  => $languages['c++'],
	'cpp'  => $languages['c++'],
	'c++'  => $languages['c++'],
	'java' => $languages['java'],
	'hs'   => $languages['haskell'],
	'lhs'  => $languages['haskell'],
	//'zip'  => $languages['zip'],
);

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
	// Files and directories
	// ---------------------------------------------------------------------
	
	// Is a file sourcecode?
	static function is_code($filename) {
		$lang = Util::language_from_filename($filename);
		return $lang['is_language'];
	}
	
	static function language_from_filename($filename) {
		$ext = pathinfo($filename, PATHINFO_EXTENSION);
		global $languages_by_extension;
		if (isset($languages_by_extension[$ext])) {
			return $languages_by_extension[$ext];
		} else {
			global $languages;
			return $languages['unknown'];
		}
	}
	static function language_info($code) {
		global $languages;
		if (isset($languages[$code])) {
			return $languages[$code];
		} else {
			return $languages['unknown'];
		}
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
