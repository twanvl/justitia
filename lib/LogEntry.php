<?php

// -----------------------------------------------------------------------------
// An logged entry of an error
// -----------------------------------------------------------------------------

class LogEntry {
	// ---------------------------------------------------------------------
	// Properties
	// ---------------------------------------------------------------------
	
	public $logid, $entity_path, $judge_host, $time, $message;
	
	// ---------------------------------------------------------------------
	// Constructing / fetching
	// ---------------------------------------------------------------------
	
	public function __construct(array $data) {
		$this->logid       = $data['logid'];
		$this->entity_path = $data['entity_path'];
		$this->judge_host  = $data['judge_host'];
		$this->time        = $data['time'];
		$this->message     = $data['message'];
	}
	
	// Fetch all errors
	public static function all() {
		static $query;
		DB::prepare_query($query, "SELECT * FROM `error_log` ORDER BY `time` DESC LIMIT 100");
		$query->execute();
		return DB::fetch_all('LogEntry',$query);
	}
	
	// Fetch all errors for the given entity
	public static function all_for_entity($entity) {
		if ($entity instanceof Entity) $entity = $entity->path();
		static $query;
		DB::prepare_query($query, "SELECT * FROM `error_log` WHERE `entity_path`=? ORDER BY `time` DESC");
		$query->execute(array($entity));
		return DB::fetch_all('LogEntry',$query);
	}
	
	private static function do_add($message, $entity=NULL, $judge=NULL) {
		$data = array(
			'entity_path' => $entity instanceof Entity ? $entity->path() : $entity,
			'message'     => $message,
		);
		
		// Look for duplicates
		static $query_get, $query_get_null, $query_add, $query_update_time;
		if ($data['entity_path'] === NULL || $data['entity_path'] === false) {
			// !(NULL = NULL)
			DB::prepare_query($query_get_null, "SELECT * FROM `error_log` WHERE `entity_path` IS NULL AND `message`=:message LIMIT 1");
			$query_get_null->execute(array('message' => $message));
			$result = DB::fetch_one('LogEntry', $query_get, '', false);
		} else {
			DB::prepare_query($query_get, "SELECT * FROM `error_log` WHERE `entity_path`=:entity_path AND `message`=:message LIMIT 1");
			$query_get->execute($data);
			$result = DB::fetch_one('LogEntry', $query_get, '', false);
		}
		if ($result) {
			DB::prepare_query($query_update_time, "UPDATE `error_log` SET `time`=:time WHERE `logid`=:logid");
			$query_update_time->execute(array(
				'logid' => $result->logid,
				'time'  => time(),
			));
			$query_update_time->closeCursor();
			return $result; // already exists
		}
		
		$data['judge_host'] = $judge instanceof JudgeDaemon ? $judge->name : $judge;
		$data['time'] = time();
		
		// Add to database
		static $query_add;
		DB::prepare_query($query_add,
			"INSERT INTO `error_log` (`judge_host`,`entity_path`,`time`,`message`)".
			                 "VALUES (:judge_host, :entity_path, :time, :message)");
		$query_add->execute($data);
		if ($query_add->rowCount() != 1) {
			throw new InternalException("Create log message failed");
		}
		$data['logid'] = DB::get()->lastInsertId();
		$query_add->closeCursor();
		return new LogEntry($data);
	}
	
	// ---------------------------------------------------------------------
	// Deleting old entries
	// ---------------------------------------------------------------------
	
	// Delete a single log entry
	public static function delete_by_id($logid) {
		if ($logid instanceof LogEntry) $logid = $logid->logid();
		static $query;
		DB::prepare_query($query, "DELETE FROM `error_log` WHERE `logid`=?");
		$query->execute(array($logid));
		$query->closeCursor();
	}
	
	// Delete all errors for the given entity
	public static function delete_by_entity($entity) {
		if ($entity instanceof Entity) $entity = $entity->path();
		static $query;
		DB::prepare_query($query, "DELETE FROM `error_log` WHERE `entity_path`=?");
		$query->execute(array($entity));
		$query->closeCursor();
	}
	
	// ---------------------------------------------------------------------
	// Safe logging wrapper
	// ---------------------------------------------------------------------
	
	// Log an error
	public function log($message, $entity=NULL, $judge=NULL) {
		if ($message instanceof Exception) {
			$message = $message->getMessage();
		}
		
		// prevent a possible loop due to reentry
		static $logging = false;
		if ($logging) return;
		$logging = true;
		
		try {
			LogEntry::do_add($message,$entity,$judge);
		} catch (Exception $e) {
			// Ignore errors during logging
		}
		
		$logging = false;
	}
}
