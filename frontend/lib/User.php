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
	// ---------------------------------------------------------------------
	// Properties
	// ---------------------------------------------------------------------
	
	private $data;
	
	function check_password($password, $throw) {
		$ok = check_salted_password_hash($password, $this->data['password']);
		if ($throw && !$ok) {
			throw new Exception("User not found: $this->login");
		}
		return $ok;
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
	
	static function names_text($array) {
		if (empty($array)) return "no one";
		$result = "";
		foreach($array as $user) {
			if (strlen($result) > 0) $result .= ', ';
			$result .= $user->name();
		}
		return $result;
	}
	static function names_html($array) {
		if (empty($array)) {
			return "<em>no one</em>";
		}
		$result = "";
		foreach($array as $user) {
			if (strlen($result) > 0) $result .= ', ';
			$result .= htmlspecialchars($user->name());
		}
		return $result;
	}
	
	// ---------------------------------------------------------------------
	// Constructing / fetching
	// ---------------------------------------------------------------------
	
	static function by_login($login) {
		static $query;
		DB::prepare_query($query, "SELECT * FROM `user` WHERE login=?");
		$query->execute(array($login));
		return User::fetch_one($query, $login);
	}
	
	private function __construct($data) {
		$this->data = $data;
	}
		
	static function fetch_one($query, $info='') {
		$data = $query->fetch(PDO::FETCH_ASSOC);
		if ($data === false) {
			throw new Exception("User not found: $info");
		}
		$query->closeCursor();
		return new User($data);
	}
	static function fetch_all($query) {
		// fetch submissions
		$result = array();
		$query->setFetchMode(PDO::FETCH_ASSOC);
		foreach($query as $user) {
			$result []= new User($user);
		}
		$query->closeCursor();
		return $result;
	}
	
	// ---------------------------------------------------------------------
	// Submissions
	// ---------------------------------------------------------------------
	
	// All submissions made by this user
	function all_submissions() {
		static $query;
		DB::prepare_query($query,
			"SELECT * FROM `user_submission` LEFT JOIN `submission` ON `user_submission`.`submissionid` = `submission`.`submissionid`".
			" WHERE `userid`=?".
			" ORDER BY `time` DESC"
		);
		$query->execute(array($this->userid));
		return Submission::fetch_all($query);
	}
	
	// All submissions made by this user to $entity
	function submissions_to($entity) {
		if (!$entity->attribute('submitable')) return array();
		static $query;
		DB::prepare_query($query,
			"SELECT * FROM `user_submission` LEFT JOIN `submission` ON `user_submission`.`submissionid` = `submission`.`submissionid`".
			" WHERE `userid`=? AND `entity_path`=?".
			" ORDER BY `time` DESC"
		);
		$query->execute(array($this->userid, $entity->path()));
		return Submission::fetch_all($query);
	}
	
	// Last submission made by user to entity
	function last_submission_to($entity) {
		if (!$entity->attribute('submitable')) return false;
		static $query;
		DB::prepare_query($query,
			"SELECT * FROM `user_submission` LEFT JOIN `submission` ON `user_submission`.`submissionid` = `submission`.`submissionid`".
			" WHERE `userid`=? AND `entity_path`=?".
			" ORDER BY `time` DESC".
			" LIMIT 1"
		);
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
