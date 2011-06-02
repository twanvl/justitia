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
		$this->update_user_entity_table();
	}
	
	function rejudge() {
		if(!$this->is_archived()) {
			self::rejudge_by_id($this->submissionid);
			$this->judge_start = 0;
			$this->judge_host  = NULL;
			$this->status      = Status::PENDING;
		}
	}
	static function rejudge_by_id($submissionid) {
		if(!$this->is_archived()) {
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
	}
	
	function delete() {
		self::delete_by_id($this->submissionid);
	}
	static function delete_by_id($submissionid) {
		// TODO this function does not yet work with the filesystem!!!
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
	
	/* 
	 * This function (re)generates the entries in the user_entity table, which is used to speed things up
	 */
	function update_user_entity_table() {
		// TODO: clean up code
		foreach($this->users() as $user) {
			DB::prepare_query($qbest, "SELECT * FROM submission AS s JOIN user_submission AS us ON s.submissionid = us.submissionid WHERE us.userid = ? AND s.entity_path = ? ORDER BY `s`.`status` DESC, `s`.`submissionid` DESC LIMIT 1");
			$qbest->execute(array($user->userid, $this->entity_path));
			$best = $qbest->fetch();
			$qbest->closeCursor();
			DB::prepare_query($qlast, "SELECT * FROM submission AS s JOIN user_submission AS us ON s.submissionid = us.submissionid WHERE us.userid = ? AND s.entity_path = ? ORDER BY `s`.`submissionid` DESC LIMIT 1");
			$qlast->execute(array($user->userid, $this->entity_path));
			$last = $qlast->fetch();
			$qlast->closeCursor();
			DB::prepare_query($insert, "REPLACE INTO `user_entity` (`userid`, `entity_path`, `last_submissionid`, `best_submissionid`) VALUES (?, ?, ?, ?)");
			$insert->execute(array($user->userid, $this->entity_path, $last['submissionid'], $best['submissionid']));
			$insert->closeCursor();
		}
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
		if(SUBMISSION_STORAGE == 'database') {
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
		} else { // SUBMISSION_STORAGE == 'filesystem'
			$submission_path = substr(SUBMISSION_PATH, -1) == "/" ? substr(SUBMISSION_PATH, 0, -1) : SUBMISSION_PATH;
			$absolute_file = $submission_path . $this->entity()->path()."submission_".$this->submissionid.'/'.$filename;
			// make sure there is a directory
			$dir = implode("/", explode("/", $absolute_file, -1));
			if(!file_exists($dir)) {
				mkdir($dir, 0777, true); // TODO CHANGE!
			}
			file_put_contents($absolute_file, $data);
		}
	}
	
	function get_file($filename) {
		if(SUBMISSION_SOURCE == 'database') {
			return $this->get_file_database($filename);
		} else if(SUBMISSION_SOURCE == 'filesystem') {
			return $this->get_file_filesystem($filename);
		} else { // SUBMISSION_SOURCE == 'both'
			if($this->file_exists_database($filename)) {
				return $this->get_file_database($filename);
			} else {
				return $this->get_file_filesystem($filename);
			}
		}
	}
	
	private function get_file_database($filename) {
		DB::prepare_query($query,
			"SELECT `data` FROM `file` WHERE `submissionid`=? AND `filename`=?");
		$query->execute(array($this->submissionid,$filename));
		DB::check_errors($query);
		return $query->fetchColumn();
	}
	
	private function get_file_filesystem($filename) {
		$submission_path = substr(SUBMISSION_PATH, -1) == "/" ? substr(SUBMISSION_PATH, 0, -1) : SUBMISSION_PATH;
		return file_get_contents($submission_path.$this->entity()->path()."submission_".$this->submissionid."/".$filename);
	}
	
	function file_exists($filename) {
		print(file_exists($filename));
		if(SUBMISSION_SOURCE == 'database') {
			return $this->file_exists_database($filename);
		} else if(SUBMISSION_SOURCE == 'filesystem') {
			return $this->file_exists_filesystem($filename);
		} else { // SUBMISSION_SOURCE == 'both'
			return $this->file_exists_database($filename) OR $this->file_exists_filesystem($filename);
		}
	}

	function file_exists_database($filename) {
		DB::prepare_query($query,
			"SELECT COUNT(*) FROM `file` WHERE `submissionid`=? AND `filename`=?");
		$query->execute(array($this->submissionid,$filename));
		DB::check_errors($query);
		return $query->fetchColumn();
	}
	
	function file_exists_filesystem($filename) {
		return file_exists($this->entity()->path()."submission_".$this->submissionid."/".$filename);
	}
	
	/**
	 * When a submission is archived the source files are backupped and not available anymore 
	 * for justitia. Only the state of the submission is saved.
	 */
	function is_archived() {
		return count($this->get_code_filenames()) == 0;
	}
	
	// Get an array of all code filenames
	// array entries are of the form ("code/<SOMETHING>" => "<SOMETHING>");
	// the key can be passed to get_file, the value is the filename
	function get_code_filenames() {
		if(SUBMISSION_SOURCE == 'database') {
			return $this->get_code_filenames_database();
		} else if(SUBMISSION_SOURCE == 'filesystem') {
			return $this->get_code_filenames_filesystem();
		} else { // SUBMISSION_SOURCE == 'both'
			if($this->file_exists_database($filename)) {
				return $this->get_code_filenames_database();
			} else {
				return $this->get_code_filenames_filesystem();
			}
		}
	}
	
	function get_code_filenames_database() {
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
	
	function get_code_filenames_filesystem() {
		$submission_path = substr(SUBMISSION_PATH, -1) == "/" ? substr(SUBMISSION_PATH, 0, -1) : SUBMISSION_PATH;
		$submission_path .= $this->entity_path . "submission_".$this->submissionid . '/code';
		return $this->get_code_filenames_filesystem_helper($submission_path, 'code/');
	}
	
	function get_code_filenames_filesystem_helper($path, $base) {
		if(is_dir($path)) {
			$files = scandir($path);
			$result = array();
			foreach($files as $f) {
				if($f != "." AND $f != "..") {
					if(is_dir($f)) {
						$result = array_merge($result, $this->get_code_filenames_filesystem_helper($path.$f.'/', $base.$f.'/'));
					} else {
						$result[$base.$f] = $f;
					}
				}
			}
			return $result;
		} else {
			// code dir is missing
			return array();
		}
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
