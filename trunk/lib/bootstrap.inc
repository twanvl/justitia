<?php

// -----------------------------------------------------------------------------
// Bootstrapping code
// -----------------------------------------------------------------------------

require_once('../config/config.php');

function __autoload($class_name) {
	if($class_name == 'LogLevel') {
		$class_name = 'Log';
	}
	require_once "../lib/$class_name.php";
}

// -----------------------------------------------------------------------------
// Fancy templates need fancy error messages
// -----------------------------------------------------------------------------

function fancy_exception_handler($e) {
	if($e instanceof InternalException) {
		Log::error($e->__toString());
	} else {
		Log::warning($e->__toString());
	}
	ErrorPage::die_fancy($e);
}

set_exception_handler('fancy_exception_handler');

error_reporting(E_ALL);

// -----------------------------------------------------------------------------
// Date/time handling
// -----------------------------------------------------------------------------

if (defined('TIMEZONE')) {
	date_default_timezone_set(TIMEZONE);
} else {
	// shut up warnings about the timezone
	@date_default_timezone_set(@date_default_timezone_get());
}

// -----------------------------------------------------------------------------
// Fix PHP idiocities
// Some idiot thought that magic_quotes was a good idea
// -----------------------------------------------------------------------------

if (get_magic_quotes_gpc()) {
	function stripslashes_gpc(&$value) {
		$value = stripslashes($value);
	}
	array_walk_recursive($_GET, 'stripslashes_gpc');
	array_walk_recursive($_POST, 'stripslashes_gpc');
	array_walk_recursive($_COOKIE, 'stripslashes_gpc');
	array_walk_recursive($_REQUEST, 'stripslashes_gpc');
	Log::error("Please set 'magic_quotes_gpc = Off', thank you.");
}
