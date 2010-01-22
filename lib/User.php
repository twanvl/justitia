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
	function name_and_login() {
		return $this->name() . ' (' . $this->login . ')';
	}
	function name_for_sort() {
		return $this->lastname.','.$this->firstname . ',' . $this->midname;
	}
	
	static function sort_users($users) {
		$sorted = array();
		foreach($users as $user) {
			$sorted[$user->name_for_sort()] = $user;
		}
		ksort($sorted);
		return $sorted;
	}
	static function names_text($users) {
		if (empty($users)) return "no one";
		// convert
		$result = "";
		foreach(User::sort_users($users) as $user) {
			if (strlen($result) > 0) $result .= ', ';
			$result .= $user->name_and_login();
		}
		return $result;
	}
	static function names_html($users) {
		if (empty($users)) return "<em>no one</em>";
		// convert
		$result = "";
		foreach(User::sort_users($users) as $user) {
			if (strlen($result) > 0) $result .= ', ';
			$result .= htmlspecialchars($user->name());
			$result .= " <small>(" . htmlspecialchars($user->login) . ")</small>";
		}
		return $result;
	}
	static function names_for_sort($users) {
		// convert
		$result = "";
		foreach(User::sort_users($users) as $user) {
			$result .= $user->name_for_sort() . '|';
		}
		return $result;
	}
	
	// ---------------------------------------------------------------------
	// Authentication
	// ---------------------------------------------------------------------
	
	function check_password($password, $throw = true) {
		$ok = $this->do_check_password($password);
		if ($throw && !$ok) {
			// Note: we throw the same error as when User::by_login fails, so an attacker doesn't learn anything
			throw new NotFoundException("User not found: $this->login");
		}
		return $ok;
	}
	
	private function do_check_password($password) {
		$auth_method = $this->data['auth_method'];
		if ($auth_method == 'ldap') {
			return $this->do_check_password_ldap($password);
		} else if ($auth_method == 'pass') {
			return $this->do_check_password_pass($password);
		} else {
			LogEntry::log("Unsupported auth_method: '$auth_method' for user " . $this->login);
			return false; // unsupported
		}
	}
	
	private function do_check_password_pass($password) {
		return check_salted_password_hash($password, $this->data['password']);
	}
	
	private function do_check_password_ldap($password) {
		if (!function_exists('ldap_connect_and_login')) return false;
		if ($con = ldap_connect_and_login($this->login, $password)) {
			ldap_unbind($con);
			return true;
		} else {
			return false;
		}
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
		DB::prepare_query($query, "SELECT userid,login,password,auth_method FROM `user` WHERE `login`=?");
		$query->execute(array($login));
		return User::fetch_one($query, $login, $throw);
	}
	
	static function all($filter = "%") {
		static $query;
		// TODO: which fields to select?
		DB::prepare_query($query,
			"SELECT userid,login,firstname,midname,lastname,email,is_admin FROM `user`".
			" WHERE `login` LIKE ?".
			"    OR CONCAT_WS(' ',`firstname`,`midname`,`lastname`) LIKE ?".
			"    OR CONCAT_WS(' ',`firstname`,`lastname`,`midname`) LIKE ?".
			" ORDER BY `lastname`,`firstname`,`midname`"
		);
		$query->execute(array($filter,$filter,$filter));
		return User::fetch_all($query);
	}
	
	static function add($data) {
		if (User::by_login($data['login'],false) !== false) {
			throw new Exception("User with that login already exists");
		}
		$data['password'] = make_salted_password_hash($data['password']);
		$data['is_admin'] = $data['is_admin']?1:0;
		if (!isset($data['notes'])) $data['notes'] = '';
		if (!isset($data['class'])) $data['class'] = '';
		if (!isset($data['auth_method'])) $data['auth_method'] = 'pass';
		static $query;
		DB::prepare_query($query,
			"INSERT INTO `user` (`login`,`password`,`auth_method`,`firstname`,`midname`,`lastname`,`email`,`class`,`notes`,`is_admin`)".
			            "VALUES (:login, :password, :auth_method, :firstname, :midname, :lastname, :email, :class, :notes, :is_admin)");
		$query->execute($data);
		if ($query->rowCount() != 1) {
			throw new Exception("Create user failed");
		}
		$data['userid'] = DB::get()->lastInsertId();
		$query->closeCursor();
		return new User($data);
	}
	static function add_from_ldap($login,$password) {
		if (!function_exists('ldap_connect_and_login')) return false;
		$con = @ldap_connect_and_login($login, $password);
		if (!$con) return false;
		// create a new user based on LDAP data
		$search = ldap_search($con, LDAP_BASE_DN, "cn=$login");
		if (!$search) return false;
		$entries = ldap_get_entries($con,$search);
		ldap_unbind($con);
		if (empty($entries)) return false;
		$data = userdata_from_ldap($entries[0]);
		if (!$data) return false;
		$data['login']    = $login;
		$data['password'] = '';
		$data['auth_method'] = 'ldap';
		$data['is_admin'] = false;
		return User::add($data);
	}
	
	function alter($data) {
		$other = User::by_login($data['login'],false);
		if ($other !== false && $other->userid != $this->userid) {
			throw new Exception("Another user with that login already exists");
		}
		if ($data['auth_method'] == 'pass') {
			if (isset($data['password'])) {
				$data['password'] = make_salted_password_hash($data['password']);
			} else {
				$data['password'] = $this->password;
			}
		} else if ($data['auth_method'] == 'ldap') {
			// keep old password
			$data['password'] = $this->password;
		} else {
			throw new InternalException("Unsupported auth_method: $auth_method");
		}
		$data['is_admin'] = $data['is_admin']?1:0;
		static $query;
		DB::prepare_query($query,
			"UPDATE `user` SET `login` = :login, `password` = :password, `auth_method` = :auth_method, `firstname` = :firstname, `midname` = :midname, `lastname` = :lastname, `email` = :email, `class` = :class, `notes` = :notes, `is_admin` = :is_admin".
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
	
	public function __construct(array $data) {
		$this->data = $data;
	}
		
	static function fetch_one($query, $info='', $throw=true) {
		return DB::fetch_one('User',$query,$info,$throw);
	}
	static function fetch_all(PDOStatement $query) {
		return DB::fetch_all('User',$query);
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
		if (!$entity->submitable()) return array();
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
		if (!$entity->submitable()) return false;
		if ($entity->attribute_bool('keep best')) {
			static $query;
			DB::prepare_query($query,
				"SELECT * FROM `user_submission` LEFT JOIN `submission` ON `user_submission`.`submissionid` = `submission`.`submissionid`".
				" WHERE `userid`=? AND `entity_path`=?".
				" ORDER BY `status` DESC, `time` DESC".
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
