<?php

// -----------------------------------------------------------------------------
// A database connection object for judge daemons
// -----------------------------------------------------------------------------

class JudgeDaemon {
	// ---------------------------------------------------------------------
	// Properties
	// ---------------------------------------------------------------------
	
	// Unique identifier of this judge daemon
	public $judgeid;
	public $name;
	// Time the daemon was started
	public $start_time;
	// Time the daemon was last active
	public $ping_time;
	// Status of the daemon, one of the values below
	public $status;
	const STOPPED      = 0; // dead and never comming back
	const ACTIVE       = 1;
	const PAUSED       = 2; // control requests that we pause
	const MUST_STOP    = 3;
	const MUST_RESTART = 4;
	
	public function status_text() {
		if ($this->status == JudgeDaemon::STOPPED)      return "stopped";
		if ($this->status == JudgeDaemon::ACTIVE)       return "active";
		if ($this->status == JudgeDaemon::PAUSED)       return "paused";
		if ($this->status == JudgeDaemon::MUST_STOP)    return "must stop";
		if ($this->status == JudgeDaemon::MUST_RESTART) return "must restart";
		else return "unknown";
	}
	public static function is_valid_status($status) {
		return $status >= 0 && $status <= 4;
	}
	
	// ---------------------------------------------------------------------
	// Constructing / fetching
	// ---------------------------------------------------------------------
	
	public function __construct(array $data) {
		$this->judgeid    = $data['judgeid'];
		$this->name       = $data['judge_host'];
		$this->status     = $data['status'];
		$this->start_time = $data['start_time'];
		$this->ping_time  = $data['ping_time'];
	}
	
	// Fetch all judge daemon records
	public static function all() {
		static $query;
		DB::prepare_query($query, "SELECT * FROM `judge_daemon`");
		$query->execute();
		return DB::fetch_all('JudgeDaemon',$query);
	}
	
	// Fetch a single judge daemon
	public static function by_id($judgeid) {
		static $query;
		DB::prepare_query($query, "SELECT * FROM `judge_daemon` WHERE `judgeid`=?");
		$query->execute(array($judgeid));
		return DB::fetch_one('JudgeDaemon',$query,$judgeid,false);
	}
	
	public static function add($status = JudgeDaemon::ACTIVE) {
		// generate a new unique identifier
		// Name of this host.
		// Add randomness, so two judges can run on one computer if needed
		$salt = '';
		for ($i = 0 ; $i < 5 ; ++$i) {
			$salt .= chr(mt_rand(65,65+25));
		}
		$unique_name = trim(`hostname`) . ' [' . $salt . ']';
		$data = array(
			'judge_host' => $unique_name,
			'status'     => $status,
			'start_time' => time(),
			'ping_time'  => time(),
		);
		// Add to database
		static $query;
		DB::prepare_query($query,
			"INSERT INTO `judge_daemon` (`judge_host`,`status`,`start_time`,`ping_time`)".
			                    "VALUES (:judge_host, :status, :start_time, :ping_time)");
		$query->execute($data);
		if ($query->rowCount() != 1) {
			throw new Exception("Create judge daemon failed");
		}
		$data['judgeid'] = DB::get()->lastInsertId();
		$query->closeCursor();
		return new JudgeDaemon($data);
	}
	
	// re-fetch the object from the database, to receive status updates
	public function update_status() {
		static $query;
		DB::prepare_query($query, "SELECT status FROM `judge_daemon` WHERE `judgeid`=?");
		$query->execute(array($this->judgeid));
		$data = $query->fetch(PDO::FETCH_ASSOC);
		DB::check_errors($query);
		$query->closeCursor();
		
		if ($data === false) {
			$this->status = JudgeDaemon::STOPPED;
		} else {
			$this->status = $data['status'];
		}
	}
	
	// ---------------------------------------------------------------------
	// Manipulation
	// ---------------------------------------------------------------------
	
	public function set_status($new_status) {
		if (!JudgeDaemon::is_valid_status($new_status)) {
			throw new Exception("Invalid status code: $new_status");
		}
		$this->status = $new_status;
		// update database
		if ($this->status == JudgeDaemon::STOPPED) {
			// remove
			static $query;
			DB::prepare_query($query,
				"DELETE FROM `judge_daemon` WHERE `judgeid` = :judgeid");
			$query->execute(array(
				'judgeid' => $this->judgeid,
			));
			$query->closeCursor();
		} else {
			static $query;
			DB::prepare_query($query,
				"UPDATE `judge_daemon` SET `status` = :status".
				" WHERE `judgeid` = :judgeid");
			$query->execute(array(
				'status'  => $this->status,
				'judgeid' => $this->judgeid,
			));
			$query->closeCursor();
		}
	}
	
	public function ping() {
		$this->ping_time = time();
		static $query;
		DB::prepare_query($query,
			"UPDATE `judge_daemon` SET `ping_time` = :ping_time".
			" WHERE `judgeid` = :judgeid");
		$query->execute(array(
			'ping_time' => $this->ping_time,
			'judgeid'   => $this->judgeid,
		));
		$query->closeCursor();
	}
}
