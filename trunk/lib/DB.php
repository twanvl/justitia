<?php

// -----------------------------------------------------------------------------
// Database connection
// -----------------------------------------------------------------------------

class DB {
	// ---------------------------------------------------------------------
	// The connections
	// ---------------------------------------------------------------------
	
	// Get the database connection
	static function get() {
		static $db;
		if (!isset($db)) {
			try {
				$db = new PDO(DB_PATH, DB_USERNAME, DB_PASSWORD, array(
					PDO::ATTR_PERSISTENT => DB_PERSISTENT
				));
			} catch (Exception $e) {
				// prevent passwords from being exposed
				throw new InternalException("Can't connect to database");
			}
		}
		return $db;
	}
	
	// ---------------------------------------------------------------------
	// Prepared queries
	// ---------------------------------------------------------------------
	
	// Prepare a query
	static function prepare($sql) {
		return DB::get()->prepare($sql);
	}
	static function prepare_query(&$query, $sql) {
		if (!isset($query)) {
			$query = DB::get()->prepare($sql);
		}
	}
	
	// Check for errors
	static function check_errors(PDOStatement $query) {
		$status = $query->errorInfo();
		if ($status[0] != 0) {
			throw new InternalException($status[2]);
		}
	}
	
	// ---------------------------------------------------------------------
	// Fetching results
	// ---------------------------------------------------------------------
	
	// Fetch one object from a query
	static function fetch_one($class, PDOStatement $query, $info='', $throw=true) {
		$data = $query->fetch(PDO::FETCH_ASSOC);
		DB::check_errors($query);
		$query->closeCursor();
		if ($data === false) {
			if ($throw) throw new NotFoundException("$class not found: $info");
			else        return false;
		}
		return new $class($data);
	}
	
	// Fetch all objects from a query
	static function fetch_all($class, PDOStatement $query) {
		// fetch submissions
		$result = array();
		$query->setFetchMode(PDO::FETCH_ASSOC);
		foreach($query as $data) {
			$result []= new $class($data);
		}
		DB::check_errors($query);
		$query->closeCursor();
		return $result;
	}
}
