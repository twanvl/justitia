<?php

// -----------------------------------------------------------------------------
// Database connection
// -----------------------------------------------------------------------------

class DB {
	// Get the database connection
	static function get() {
		static $db;
		if (!isset($db)) {
			$db = new PDO(DB_PATH, DB_USERNAME, DB_PASSWORD, array(
				PDO::ATTR_PERSISTENT => DB_PERSISTENT
			));
		}
		return $db;
	}
	
	// Prepare a query
	static function prepare($sql) {
		return DB::get()->prepare($sql);
	}
	static function prepare_query(&$query, $sql) {
		if (!isset($query)) {
			$query = DB::get()->prepare($sql);
		}
	}
	
	// Close a cursor, and check for errors
	// TODO
}
