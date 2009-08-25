<?php


class Submission {
	// ---------------------------------------------------------------------
	// Status codes
	// ---------------------------------------------------------------------
	
	// Status codes
	const STATUS_FAILED   = 0;
	const STATUS_PASSED   = 1;
	const STATUS_PENDING  = 2; // submission still in judge queue
	const STATUS_NOT_DONE = 3; // no submission attempt has been made
	
	// ---------------------------------------------------------------------
	// Data
	// ---------------------------------------------------------------------
	
	// Record data
	private $data;
	
	function __get($attr) {
		return $this->data[$attr];
	}
	
	function textual_status() {
		switch ($this->status) {
			case Submission::STATUS_FAILED:   return "Failed";
			case Submission::STATUS_PASSED:   return "Passed";
			case Submission::STATUS_PENDING:  return "Pending";
			case Submission::STATUS_NOT_DONE: return "Not submitted";
		}
	}
	
	function users() {
		static $query;
		if (!isset($query)) {
			$query = db()->prepare(
				"SELECT * FROM `user_submission`".
				" LEFT JOIN `user` ON `user_submission`.`userid` = `user`.`userid`".
				" WHERE submissionid=?"
			);
		}
		$query->execute(array($this->submissionid));
		return User::fetch_all($query);
	}
	
	// ---------------------------------------------------------------------
	// Constructing / fetching
	// ---------------------------------------------------------------------
	
	static function by_id($submissionid) {
		static $query;
		if (!isset($query)) {
			$query = db()->prepare("SELECT * FROM `submission` WHERE submissionid=?");
		}
		$query->execute(array($submissionid));
		return Submission::fetch_one($query,$submissionid);
	}
	
	/*// dummy submission
	static function no_submission() {
		return new Submission(array('status' => STATUS_NOT_DONE));
	}*/
	
	function __construct($data) {
		$this->data = $data;
	}
	
	static function fetch_one($query, $info='') {
		$data = $query->fetch(PDO::FETCH_ASSOC);
		if ($data === false) {
			throw new Exception("Submission not found: $info");
		}
		$query->closeCursor();
		return new Submission($data);
	}
	static function fetch_all($query) {
		// fetch submissions
		$result = array();
		$query->setFetchMode(PDO::FETCH_ASSOC);
		foreach($query as $subm) {
			$result []= new Submission($subm);
		}
		$query->closeCursor();
		return $result;
	}
	
	// ---------------------------------------------------------------------
	// Updating
	// ---------------------------------------------------------------------
	
	// Add a new submission to the database, and return it
	static function make_new($entity,$file_path) {
		static $query;
		if (!isset($query)) {
			$query = db()->prepare(
				"INSERT INTO `submission`".
				       " (`time`,`entity_path`,`file_path`,`judge_host`,`judge_start`,`status`)".
				" VALUES (:time, :entity_path, :file_path, NULL,        0,            ". STATUS_PENDING .")");
		}
		$data = array(
			'time'        => time(),
			'entity_path' => $entity->path(),
			'file_path'   => file_path,
			'judge_host'  => NULL,
			'judge_start' => 0,
			'status'      => STATUS_PENDING
		);
		$query->execute($data);
		$data['submissionid'] = db()->lastInsertId();
		$query->closeCursor();
		return new Submission($data);
	}
	
	// Add a user <-> submission relation
	function add_user($user) {
		static $query;
		if (!isset($query)) {
			$query = db()->prepare("INSERT INTO `user_submission` VALUES (?,?)");
		}
		// note: it is possible that inserting fails, if the relation already exists
		$query->execute(array($user->userid, $this->submissionid));
		$query->closeCursor();
	}
	
	// ---------------------------------------------------------------------
	// For judge hosts
	// ---------------------------------------------------------------------
	
	// Get a pending submission, if there are any
	// it is then assigned to this judge_host
	static function get_pending_submission($host) {
		static $query_check, $query_take, $query_fetch;
		if (!isset($query_check)) {
			$query_check = db()->prepare(
				"SELECT COUNT(*) FROM `submission` WHERE `status` = 2 AND `judge_start` < :old_start");
			$query_take = db()->prepare(
				"UPDATE `submission` SET `judge_start` = :new_start, `judge_host` = :host" .
				" WHERE `status` = 2 AND `judge_start` < :old_start".
				" LIMIT 1");
			$query_fetch = db()->prepare(
				"SELECT * FROM `submission`" .
				" WHERE `status` = 2 AND `judge_start` = :new_start, `judge_host` = :host");
		}
		
		// are there pending submissions?
		// this step is to make things faster
		$params = array(
			'old_start' => time() - REJUDGE_TIMEOUT,
			'new_start' => time(),
			'host'      => $host
		);
		$query_check->execute($params);
		list($num) = $query_check->fetch(PDO::FETCH_NUM);
		$query_check->closeCursor();
		if ($num == 0) {
			return false;
		}
		
		// if so, take one
		$query_take->execute($params);
		$num = $query_take->rowCount();
		$query_check->closeCursor();
		if ($num == 0) {
			return false;
		}
		
		// and return it
		$query_fetch->execute($params);
		return Submission::fetch_one($query_fetch);
	}
}
