<?php

// -----------------------------------------------------------------------------
// Bootstrapping code
// -----------------------------------------------------------------------------

require_once('../config/config.php');

function __autoload($class_name) {
	require_once "../lib/$class_name.php";
}

// -----------------------------------------------------------------------------
// Fancy templates need fancy error messages
// -----------------------------------------------------------------------------

function fancy_exception_handler($e) {
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
