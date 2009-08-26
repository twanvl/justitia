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
	
	function check_password($password, $throw = true) {
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
	
	function data() {
		return $this->data;
	}
	
	function name() {
		$mid = $this->midname;
		if ($mid != '') $mid .= ' ';
		return $this->firstname . ' ' . $mid . $this->lastname;
	}
	function sort_name() {
		return $this->lastname.','.$this->firstname . ',' . $this->midname;
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
	
	static function by_id($userid, $throw=true) {
		static $query;
		DB::prepare_query($query, "SELECT * FROM `user` WHERE `userid`=?");
		$query->execute(array($userid));
		return User::fetch_one($query, $userid, $throw);
	}
	static function by_login($login, $throw=true) {
		static $query;
		DB::prepare_query($query, "SELECT * FROM `user` WHERE `login`=?");
		$query->execute(array($login));
		return User::fetch_one($query, $login, $throw);
	}
	
	static function all($filter = "%") {
		static $query;
		DB::prepare_query($query,
			"SELECT * FROM `user`".
			" WHERE `login` LIKE ? OR CONCAT(`firstname`,' ',`midname`,' ',`lastname`) LIKE ?".
			" ORDER BY `lastname`,`firstname`"
		);
		$query->execute(array($filter,$filter));
		return User::fetch_all($query);
	}
	
	static function add($data) {
		if (User::by_login($data['login'],false) !== false) {
			throw new Exception("User with that login already exists");
		}
		$data['password'] = make_salted_password_hash($data['password']);
		$data['is_admin'] = $data['is_admin']?1:0;
		static $query;
		DB::prepare_query($query,
			"INSERT INTO `user` (`login`,`password`,`firstname`,`midname`,`lastname`,`is_admin`)".
			            "VALUES (:login, :password, :firstname, :midname, :lastname, :is_admin)");
		$query->execute($data);
		if ($query->rowCount() != 1) {
			throw new Exception("Create user failed");
		}
		$data['userid'] = DB::get()->lastInsertId();
		$query->closeCursor();
		return new User($data);
	}
	
	function alter($data) {
		$other = User::by_login($data['login'],false);
		if ($other !== false && $other->userid != $this->userid) {
			throw new Exception("Another user with that login already exists");
		}
		if (isset($data['password'])) {
			$data['password'] = make_salted_password_hash($data['password']);
		} else {
			$data['password'] = $this->password;
		}
		$data['is_admin'] = $data['is_admin']?1:0;
		static $query;
		DB::prepare_query($query,
			"UPDATE `user` SET `login` = :login, `password` = :password, `firstname` = :firstname, `midname` = :midname, `lastname` = :lastname, `is_admin` = :is_admin".
			" WHERE `userid` = :userid");
		$query->execute($data);
		$query->closeCursor();
	}
	
	/*static function delete($login) {
		// delete user
		static $query;
		DB::prepare_query($query, "DELETE FROM `user` WHERE login=?");
		$query->execute(array($login));
		$query->closeCursor();
		// delete submissions
		// TODO : do we want this?
	}*/
	
	private function __construct($data) {
		$this->data = $data;
	}
		
	static function fetch_one($query, $info='', $throw=true) {
		$data = $query->fetch(PDO::FETCH_ASSOC);
		$query->closeCursor();
		if ($data === false) {
			if ($throw) throw new Exception("User not found: $info");
			else        return false;
		}
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
		if (!$entity->attribute_bool('submitable')) return array();
		static $query;
		DB::prepare_query($query,
			"SELECT * FROM `user_submission` LEFT JOIN `submission` ON `user_submission`.`submissionid` = `submission`.`submissionid`".
			" WHERE `userid`=? AND `entity_path`=?".
			" ORDER BY `time` DESC"
		);
		$query->execute(array($this->userid, $entity->path()));
		return Submission::fetch_all($query);
	}
	
	// Last/best submission made by user to entity
	function last_submission_to($entity) {
		if (!$entity->attribute_bool('submitable')) return false;
		if ($entity->attribute_bool('keep best')) {
			static $query;
			DB::prepare_query($query,
				"SELECT * FROM `user_submission` LEFT JOIN `submission` ON `user_submission`.`submissionid` = `submission`.`submissionid`".
				" WHERE `userid`=? AND `entity_path`=?".
				" ORDER BY `status` DESC".
				" LIMIT 1"
			);
			$query->execute(array($this->userid, $entity->path()));
			return Submission::fetch_one($query,'',false);
		} else {
			static $query;
			DB::prepare_query($query,
				"SELECT * FROM `user_submission` LEFT JOIN `submission` ON `user_submission`.`submissionid` = `submission`.`submissionid`".
				" WHERE `userid`=? AND `entity_path`=?".
				" ORDER BY `time` DESC".
				" LIMIT 1"
			);
			$query->execute(array($this->userid, $entity->path()));
			return Submission::fetch_one($query,'',false);
		}
	}
	function status_of_last_submission_to($entity) {
		return Status::to_status($this->last_submission_to($entity));
	}
}
