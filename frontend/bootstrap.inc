<?php

// -----------------------------------------------------------------------------
// Bootstrapping code
// -----------------------------------------------------------------------------

require_once('../config/config.php');

function __autoload($class_name) {
	require_once "lib/$class_name.php";
}
