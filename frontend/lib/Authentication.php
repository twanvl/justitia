<?php

class Authentication {
	static function require_admin() {
	}
	// Get the currently loged in user
	static function current_user() {
	}
	// Get the current team used by the user
	static function current_team() {
	}
}

// Make a hash of a password with added salt, i.e. sha1($salt.$pass).$salt
function make_salted_password_hash($password) {
	// make some salt
	$salt = '';
	for ($i = 0 ; $i < 10 ; ++$i) {
		$salt .= chr(mt_rand(48,125));
	}
	// mix
	$salty = substr($salt,0,5) . $password . substr($salt,5);
	return sha1($salty) . $salt;
}
// Check whether a password matches a given salted hash
function check_salted_password_hash($password, $password_hash) {
	$salt = substr($password_hash,40);
	$salty = substr($salt,0,5) . $password . substr($salt,5);
	return sha1($salty) == substr($password_hash,0,40);
}

class User {
	private $data;
	
	// Create the necessary tables
	static function init_database() {
		Authentication::require_admin();
		db()->execute(
			"CREATE TABLE
			"
		);
	}
	
	function __construct($userid) {
	}
	
	// Retrieve a user from the database
	static function retrieve($username) {
		$u = db()->perpare("SELECT * FROM `user` WHERE username=?")
		         ->execute(array($username));
		
	}
	
	function check_password($password) {
	}
}
