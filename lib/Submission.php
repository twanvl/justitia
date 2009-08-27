<?php

// -----------------------------------------------------------------------------
// Submissions
// -----------------------------------------------------------------------------

class Submission {
	
	// ---------------------------------------------------------------------
	// Data
	// ---------------------------------------------------------------------
	
	// Record data
	private $data;
	
	function __get($attr) {
		return $this->data[$attr];
	}
	
	function status() {
		if ($this->status == Status::PENDING) {
			if ($this->judge_start >= time() - REJUDGE_TIMEOUT) {
				return Status::JUDGING;
			} else {
				return Status::PENDING;
			}
		} else {
			return $this->status;
		}
	}
	
	function users() {
		static $query;
		DB::prepare_query($query,
			"SELECT * FROM `user_submission`".
			" LEFT JOIN `user` ON `user_submission`.`userid` = `user`.`userid`".
			" WHERE submissionid=?"
		);
		$query->execute(array($this->submissionid));
		return User::fetch_all($query);
	}
	
	function userids() {
		static $query;
		DB::prepare_query($query,
			"SELECT `userid` FROM `user_submission` WHERE submissionid=?"
		);
		$query->execute(array($this->submissionid));
		return $query->fetchAll(PDO::FETCH_COLUMN,0);
	}
	
	function is_made_by($user) {
		static $query;
		DB::prepare_query($query,
			"SELECT COUNT(*) FROM `user_submission` WHERE userid=? AND submissionid=?"
		);
		$query->execute(array($user->userid,$this->submissionid));
		$num = $query->fetchColumn();
		$query->closeCursor();
		return $num > 0;
	}
	
	function entity() {
		return Entity::get($this->entity_path);
	}
	
	// ---------------------------------------------------------------------
	// Constructing / fetching
	// ---------------------------------------------------------------------
	
	static function by_id($submissionid) {
		static $query;
		DB::prepare_query($query,
			"SELECT * FROM `submission` WHERE submissionid=?"
		);
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
	
	static function fetch_one($query, $info='', $throw = true) {
		$data = $query->fetch(PDO::FETCH_ASSOC);
		$query->closeCursor();
		if ($data === false) {
			if ($throw) throw new Exception("Submission not found: $info");
			else        return false;
		}
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
	static function make_new($entity,$file_path,$file_name) {
		static $query;
		DB::prepare_query($query,
			"INSERT INTO `submission`".
			       " (`time`,`entity_path`,`file_path`,`file_name`,`judge_host`,`judge_start`,`status`)".
			" VALUES (:time, :entity_path, :file_path, :file_name,  NULL,        0,            :status)"
		);
		$data = array();
		$data['time']         = time();
		$data['entity_path']  = $entity->path();
		$data['file_path']    = $file_path;
		$data['file_name']    = $file_name;
		$data['status']       = Status::PENDING;
		$query->execute($data);
		$data['judge_host']   = NULL;
		$data['judge_start']  = 0;
		$data['submissionid'] = DB::get()->lastInsertId();
		$query->closeCursor();
		return new Submission($data);
	}
	
	// Add a user <-> submission relation
	function add_user($user) {
		static $query;
		DB::prepare_query($query,"INSERT INTO `user_submission` VALUES (?,?)");
		// note: it is possible that inserting fails, if the relation already exists
		$query->execute(array($user->userid, $this->submissionid));
		$query->closeCursor();
	}
	
	// Alter the status of the submission
	// This also moves the associated files
	function set_status($new_status) {
		// what will the new filename be?
		// this can be as fancy as we like
		if      (Status::is_passed($new_status)) $statusdir = "passed";
		else if (Status::is_failed($new_status)) $statusdir = "failed";
		else {
			throw new Exception("Can't set status to: ".$new_status);
		}
		$new_path_base = SUBMISSION_DIR . $this->entity_path . $statusdir;
		$new_path = $new_path_base . '/' . $this->submissionid;
		// move
		@mkdir($new_path_base,0777,true);
		rename($this->file_path, $new_path);
		// update db
		static $query;
		DB::prepare_query($query,
			"UPDATE `submission` SET `file_path` = :file_path, `status` = :status".
			" WHERE `submissionid` = :submissionid");
		$query->execute(array(
			'file_path'    => $new_path,
			'status'       => $new_status,
			'submissionid' => $this->submissionid
		));
		$query->closeCursor();
		// update object
		$this->status    = $new_status;
		$this->file_path = $new_path;
		
	}
	
	// ---------------------------------------------------------------------
	// Files
	// ---------------------------------------------------------------------
	
	function code_filename() {
		return $this->file_path . '/' . $this->file_name;
	}
	function output_filename($filename) {
		return $this->file_path . '/out/' . $filename;
	}
	function input_filename($filename) {
		return COURSE_DIR . $this->entity_path . $filename;
	}
	
	// ---------------------------------------------------------------------
	// For judge hosts
	// ---------------------------------------------------------------------
	
	// Get a pending submission, if there are any
	// it is then assigned to this judge_host
	static function get_pending_submission($host) {
		static $query_check, $query_take, $query_fetch;
		DB::prepare_query($query_check,
			"SELECT COUNT(*) FROM `submission` WHERE `status` = ".Status::PENDING." AND `judge_start` < :old_start");
		DB::prepare_query($query_take,
			"UPDATE `submission` SET `judge_start` = :new_start, `judge_host` = :host" .
			" WHERE `status` = ".Status::PENDING." AND `judge_start` < :old_start".
			" LIMIT 1");
		DB::prepare_query($query_fetch,
			"SELECT * FROM `submission`" .
			" WHERE `status` = ".Status::PENDING." AND `judge_start` = :new_start AND `judge_host` = :host");
		
		// are there pending submissions?
		// this step is to make things faster
		$params = array();
		$params['old_start'] = time() - REJUDGE_TIMEOUT;
		$query_check->execute($params);
		$num = $query_check->fetchColumn();
		$query_check->closeCursor();
		if ($num == 0) {
			return false;
		}
		
		// if so, take one
		$params['new_start'] = time();
		$params['host']      = $host;
		$query_take->execute($params);
		$num = $query_take->rowCount();
		$query_check->closeCursor();
		if ($num == 0) {
			return false;
		}
		
		// and return it
		unset($params['old_start']);
		$query_fetch->execute($params);
		return Submission::fetch_one($query_fetch);
	}
}
