<?php

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
	
	function __construct($login) {
		$stmt = db()->prepare("SELECT * FROM `user` WHERE login=?");
		$stmt->execute(array($login));
		$this->data = $stmt->fetch(PDO::FETCH_ASSOC);
		if ($this->data === false) {
			throw new Exception("User not found: $login");
		}
	}
	
	function check_password($password) {
		return check_salted_password_hash($password, $this->data['password']);
	}
	
	function set_password($password) {
		$this->data['password'] = make_salted_password_hash($password);
	}
}
