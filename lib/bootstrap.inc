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
