<?php

// -----------------------------------------------------------------------------
// Bootstrapping code
// -----------------------------------------------------------------------------

require_once('../config/config.php');

function __autoload($class_name) {
	require_once "lib/$class_name.php";
}

// -----------------------------------------------------------------------------
// Database connection
// -----------------------------------------------------------------------------

// TODO: move
// Get a database connection
function db() {
	static $db;
	if (!isset($db)) {
		$db = new PDO(DB_PATH, DB_USERNAME, DB_PASSWORD, array(
			PDO::ATTR_PERSISTENT => DB_PERSISTENT
		));
	}
	return $db;
}
