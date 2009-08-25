<?php

// -----------------------------------------------------------------------------
// Password hashing
// -----------------------------------------------------------------------------

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

// -----------------------------------------------------------------------------
// Users from the database
// -----------------------------------------------------------------------------

class User {
	private $data;
	
	function __construct($login) {
		static $query;
		if (!isset($query)) {
			$query = db()->prepare("SELECT * FROM `user` WHERE login=?");
		}
		$query->execute(array($login));
		$this->data = $query->fetch(PDO::FETCH_ASSOC);
		$query->closeCursor();
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
	
	function __get($attr) {
		return $this->data[$attr];
	}
	
	function name() {
		$mid = $this->midname;
		if ($mid != '') $mid .= ' ';
		return $this->firstname . ' ' . $mid . $this->lastname;
	}
	
	// ---------------------------------------------------------------------
	// Submissions
	// ---------------------------------------------------------------------
	
	// All submissions made by this user
	function all_submissions() {
		static $query;
		if (!isset($query)) {
			$query = db()->prepare(
				"SELECT * FROM `user_submission` LEFT JOIN `submission` ON `user_submission`.`submissionid` = `submission`.`submissionid`".
				" WHERE `userid`=?".
				" ORDER BY `time` DESC"
			);
		}
		$query->execute(array($this->userid));
		return Submission::fetch_all($query);
	}
	
	// All submissions made by this user to $entity
	function submissions_to($entity) {
		if (!$entity->attribute('submitable')) return array();
		static $query;
		if (!isset($query)) {
			$query = db()->prepare(
				"SELECT * FROM `user_submission` LEFT JOIN `submission` ON `user_submission`.`submissionid` = `submission`.`submissionid`".
				" WHERE `userid`=? AND `entity_path`=?".
				" ORDER BY `time` DESC"
			);
		}
		$query->execute(array($this->userid, $entity->path()));
		return Submission::fetch_all($query);
	}
	
	// Last submission made by user to entity
	function last_submission_to($entity) {
		if (!$entity->attribute('submitable')) return false;
		static $query;
		if (!isset($query)) {
			$query = db()->prepare(
				"SELECT * FROM `user_submission` LEFT JOIN `submission` ON `user_submission`.`submissionid` = `submission`.`submissionid`".
				" WHERE `userid`=? AND `entity_path`=?".
				" ORDER BY `time` DESC".
				" LIMIT 1"
			);
		}
		$query->execute(array($this->userid, $entity->path()));
		// fetch submissions
		$data = $query->fetch(PDO::FETCH_ASSOC);
		$query->closeCursor();
		if ($data === false) {
			// no submission
			return false;
		} else {
			return new Submission($data);
		}
	}
}
