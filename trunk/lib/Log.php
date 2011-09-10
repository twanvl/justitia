<?php

// -----------------------------------------------------------------------------
// A class for logging
// -----------------------------------------------------------------------------

require_once "../lib/DateRange.php";

class LogLevel {
	const VERBOSE = 0;
	const INFO = 1;
	const WARNING = 2;
	const ERROR = 3;

	public static function toString($loglevel) {
		switch($loglevel) {
			case self::VERBOSE:
				return 'verbose';
			case self::INFO:
				return 'info';
			case self::WARNING:
				return 'warning';
			case self::ERROR:
				return 'error';
		}
	}
}

class Log {
	public $logid;
	public $time;
	public $message;
	public $level;
	public $entity_path;
	public $judge_host;
	public $userid;
	public $ip;
	

	
	// ---------------------------------------------------------------------
	// Functions for reporting events
	// ---------------------------------------------------------------------
	
	public static function verbose($message, $entity_path = null, $judge_host = null) {
		self::log_entry(LogLevel::VERBOSE, $message, $entity_path, $judge_host);
	}

	public static function info($message, $entity_path = null, $judge_host = null) {
		self::log_entry(LogLevel::INFO, $message, $entity_path, $judge_host);
	}

	public static function warning($message, $entity_path = null, $judge_host = null) {
		self::log_entry(LogLevel::WARNING, $message, $entity_path, $judge_host);
	}

	public static function error($message, $entity_path = null, $judge_host = null) {
		self::log_entry(LogLevel::ERROR, $message, $entity_path, $judge_host);
	}

	private static function log_entry($loglevel, $message, $entity_path = null, $judge_host = null) {
		if(LOG_FILE_ENABLED) {
			self::log_file($loglevel, $message, $entity_path, $judge_host);
		}
		if(LOG_DATABASE_ENABLED) {
			self::log_database($loglevel, $message, $entity_path, $judge_host);
		}
		if(LOG_EMAIL_ENABLED) {
			self::log_email($loglevel, $message, $entity_path, $judge_host);
		}
	}

	// Make sure $string has the length $length
	private static function format_string($string, $length) {
		if($string == null) {
			$string = "";
		}
		$strlen = strlen($string);
		if($strlen > $length) {
			return substr($string, 0, $length-3).'...';
		}
		while($strlen < $length) {
			$string .= " ";
			$strlen++;
		}
		return $string;
	}

	private static function log_database($loglevel, $message, $entity_path = null, $judge_host = null) {
		if($loglevel >= LOG_DATABASE_LEVEL) {
			static $query_add;
			DB::prepare_query($query_add, "INSERT INTO `log` (`level`, `time`, `entity_path`, `judge_host`, `userid`, `ip`, `message`) VALUES (:level, :time, :entity_path, :judge_host, :userid, :ip, :message)");
			$query_add->execute(array('level' => $loglevel, 'time' => time(), 'entity_path' => $entity_path, 'judge_host' => $judge_host, 'message' => $message, 'userid' => self::userid(), 'ip' => self::ip()));	
		}
	}

	private static function log_file($loglevel, $message, $entity_path = null, $judge_host = null) {
		if($loglevel >= LOG_FILE_LEVEL) {
			$line  = date('Y-m-d H:i:s') . ' ';
			$line .= self::format_string(strtoupper(LogLevel::toString($loglevel)), 8);
			$line .= self::format_string($entity_path, 40) . ' - ';
			$line .= self::format_string($judge_host, 17) . ' - ';
			$line .= self::format_string((self::userid() != null) ? User::by_id(self::userid())->name_and_login() : '', 20) . ' - ';
			$line .= self::format_string(self::ip(), 15) . ' - ';
			$line .= $message;
			$handle = fopen(LOG_FILE_DIR . '/justitia_' . date("Y-m") . '.log', 'a');
			fwrite($handle, $line."\n");
			fclose($handle);
		}
	}

	private static function log_email($loglevel, $message, $entity_path = null, $judge_host = null) {
		if($loglevel >= LOG_EMAIL_LEVEL) {
			$body  = "Justitia produced a log entry.\n\n";
			$body .= "Type entry: " . LogLevel::toString($loglevel) . "\n";
			$body .= "Date: " . format_date(time()) . "\n";
			if($entity_path != null) {
				$body .= "Entity path: " . $entity_path . "\n";
			}
			if($judge_host != null) {
				$body .= "Judge host: " . $judge_host . "\n";
			}
			if(self::userid() != null) {
				$body .= "User: " . User::by_id(self::userid())->name_and_login() . "\n";
			}
			if(self::ip() != null) {
				$body .= "IP address: " . self::ip() . "\n";
			}
			$body .= "Message: " . $message . "\n";
			mail(LOG_EMAIL_EMAIL_ADRESSES, "Justitia logging", $body);
		}
	}
	
	private static function userid() {
		if(isset($_SESSION['userid'])) {
			return $_SESSION['userid'];
		} else {
			return null;
		}
	}
	
	private static function ip() {
		if(isset($_SERVER["REMOTE_ADDR"])) {
			return $_SERVER["REMOTE_ADDR"];
		} else {
			return null;
		}
	}
	
	// ---------------------------------------------------------------------
	// Functions for fetching events (only from the database)
	// ---------------------------------------------------------------------
	
	public static function fetch_last($num) {
		static $query;
		DB::prepare_query($query, "SELECT * FROM log ORDER BY `logid` DESC LIMIT ".intval($num));
		$query->execute();
		$rows = $query->fetchAll();
		$query->closeCursor();
		$result = array();
		foreach($rows as $row) {
			$o = new Log();
			$o->logid = $row['logid'];
			$o->time = $row['time'];
			$o->message = $row['message'];
			$o->level = $row['level'];
			$o->entity_path = $row['entity_path'];
			$o->judge_host = $row['judge_host'];
			$o->userid = $row['userid'];
			$o->ip = $row['ip'];
			$result[] = $o;
		}
		return $result;
	}
	
}