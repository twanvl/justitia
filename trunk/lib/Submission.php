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
	private $users; // cache
	
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
		if (isset($this->users)) return $this->users;
		static $query;
		DB::prepare_query($query,
			"SELECT user.userid as userid,login,firstname,midname,lastname,email FROM `user_submission`".
			" LEFT JOIN `user` ON `user_submission`.`userid` = `user`.`userid`".
			" WHERE submissionid=?"
		);
		$query->execute(array($this->submissionid));
		$this->users = User::fetch_all($query);
		return $this->users;
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
		// it is not a fetal error if the entity does not exist
		return Entity::get($this->entity_path, false, false);
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
	
	static function latest($start,$num) {
		DB::prepare_query($query,
			"SELECT * FROM `submission` ORDER BY `time` DESC LIMIT " . (int)$start .",". (int)$num
		);
		$query->execute(array());
		DB::check_errors($query);
		return Submission::fetch_all($query);
	}
	
	function __construct($data) {
		$this->data = $data;
	}
	
	static function fetch_one($query, $info='', $throw = true) {
		return DB::fetch_one('Submission',$query,$info,$throw);
	}
	static function fetch_all($query) {
		return DB::fetch_all('Submission',$query);
	}
	
	// ---------------------------------------------------------------------
	// Updating
	// ---------------------------------------------------------------------
	
	// Add a new submission to the database, and return it
	// the submission has status UPLOADING, so it will not yet be judged
	// to finalize the submission, call set_status(Status::PENDING)
	static function make_new($entity) {
		// store submission
		static $query;
		DB::prepare_query($query,
			"INSERT INTO `submission`".
			       " (`time`,`entity_path`,`judge_host`,`judge_start`,`status`)".
			" VALUES (:time, :entity_path,  NULL,        0,            :status)"
		);
		$data = array();
		$data['time']         = time();
		$data['entity_path']  = $entity->path();
		$data['status']       = Status::UPLOADING; // update after putting file
		if (!$query->execute($data)) {
			DB::check_errors($query);
		}
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
	function set_status($new_status) {
		// update db
		static $query;
		DB::prepare_query($query,
			"UPDATE `submission` SET `status` = :status".
			" WHERE `submissionid` = :submissionid");
		$query->execute(array(
			'status'       => $new_status,
			'submissionid' => $this->submissionid
		));
		$query->closeCursor();
		// update object
		$this->status = $new_status;
		
	}
	
	function rejudge() {
		self::rejudge_by_id($this->submissionid);
		$this->judge_start = 0;
		$this->judge_host  = NULL;
		$this->status      = Status::PENDING;
	}
	static function rejudge_by_id($submissionid) {
		// delete output files
		$query = DB::prepare(
			"DELETE FROM `file` WHERE submissionid=? AND (`filename` LIKE 'out/%' OR `filename`='testcases')"
		);
		$query->execute(array($submissionid));
		DB::check_errors($query);
		// set status to pending
		$query = DB::prepare(
			"UPDATE `submission` SET `judge_start`=0, `judge_host`=NULL, status=? WHERE submissionid=?"
		);
		$query->execute(array(Status::PENDING,$submissionid));
		DB::check_errors($query);
	}
	
	function delete() {
		self::delete_by_id($this->submissionid);
	}
	static function delete_by_id($submissionid) {
		// delete files
		$query = DB::prepare("DELETE FROM `file` WHERE submissionid=?");
		$query->execute(array($submissionid));
		DB::check_errors($query);
		// delete user_submission
		$query = DB::prepare("DELETE FROM `user_submission` WHERE submissionid=?");
		$query->execute(array($submissionid));
		DB::check_errors($query);
		// delete submission itself
		$query = DB::prepare("DELETE FROM `submission` WHERE submissionid=?");
		$query->execute(array($submissionid));
		DB::check_errors($query);
	}
	
	
	// ---------------------------------------------------------------------
	// Files in the database
	// Names of files:
	//   code/<SOMETHING>  =  submitted code files
	//   in/<SOMETHING>    =  testcase inputs and reference output (not stored in the database)
	//   out/<SOMETHING>   =  output files from compiling and running tests
	//   testcases         =  summary of test results, serialized php array
	// ---------------------------------------------------------------------
	
	function put_file($filename,$data) {
		DB::prepare_query($query,
			// this is a mysql-ism
			"REPLACE INTO `file` (`submissionid`,`filename`,`data`)".
			            " VALUES (:submissionid, :filename, :data)");
		$query->execute(array(
			'submissionid' => $this->submissionid,
			'filename'     => $filename,
			'data'         => $data,
		));
		DB::check_errors($query);
	}
	
	function get_file($filename) {
		DB::prepare_query($query,
			"SELECT `data` FROM `file` WHERE `submissionid`=? AND `filename`=?");
		$query->execute(array($this->submissionid,$filename));
		DB::check_errors($query);
		return $query->fetchColumn();
	}
	
	function file_exists($filename) {
		DB::prepare_query($query,
			"SELECT COUNT(*) FROM `file` WHERE `submissionid`=? AND `filename`=?");
		$query->execute(array($this->submissionid,$filename));
		DB::check_errors($query);
		return $query->fetchColumn();
	}
	
	// Get an array of all code filenames
	// array entries are of the form ("code/<SOMETHING>" => "<SOMETHING>");
	function get_code_filenames() {
		DB::prepare_query($query,
			"SELECT filename FROM `file` WHERE `submissionid`=? AND `filename` LIKE 'code/%' ORDER BY `filename`");
		$query->execute(array($this->submissionid));
		DB::check_errors($query);
		$code_names = $query->fetchAll(PDO::FETCH_COLUMN);
		$names = array();
		foreach ($code_names as $code_name) {
			if (substr($code_name,0,5) != 'code/') continue; // shouldn't happend because of query
			$name = substr($code_name,5);
			$names[$code_name] = $name;
		}
		return $names;
	}
	
	// ---------------------------------------------------------------------
	// Files
	// ---------------------------------------------------------------------
	
	function code_filename($filename) {
		return 'code/' . $filename;
	}
	function output_filename($filename) {
		return 'out/' . $filename;
	}
	function input_filename($filename) {
		// Same as Entity::testcase_input / testcase_output
		$path = COURSE_DIR . $this->entity_path . $filename;
		if (file_exists($path)) return $path;
		$path = COURSE_DIR . $this->entity_path . ".generated/" . $filename;
		return $path;
	}
	
	function output_exists($filename) {
		return $this->file_exists($this->output_filename($filename));
	}
	function input_exists($filename) {
		return file_exists($this->input_filename($filename));
	}
	function code_exists($filename) {
		return file_exists($this->code_filename($filename));
	}
	
	// ---------------------------------------------------------------------
	// For judge hosts
	// ---------------------------------------------------------------------
	
	// Get a pending submission, if there are any
	// it is then assigned to this judge_host
	static function get_pending_submission($host) {
		static $query_check, $query_take, $query_fetch;
		DB::prepare_query($query_check,
			"SELECT COUNT(*) FROM `submission`".
			" WHERE `status` = ".Status::PENDING." AND `judge_start` < :old_start");
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
		DB::check_errors($query_check);
		$query_check->closeCursor();
		if ($num == 0) {
			return false;
		}
		
		// if so, take one
		$params['new_start'] = time();
		$params['host']      = $host;
		$query_take->execute($params);
		$num = $query_take->rowCount();
		DB::check_errors($query_take);
		$query_take->closeCursor();
		if ($num == 0) {
			echo "Submission stolen from under our nose\n";
			return false;
		}
		
		// and return it
		unset($params['old_start']);
		$query_fetch->execute($params);
		DB::check_errors($query_fetch);
		return Submission::fetch_one($query_fetch);
	}
}
