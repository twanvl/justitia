<?php

// -----------------------------------------------------------------------------
// Status codes
// -----------------------------------------------------------------------------

class Status {
	const NOT_DONE        = 0;        // no submission attempt has been made
	const PENDING         = 10000000; // submission still in judge queue
	const JUDGING         = 11000000; // submission still in judge queue, but being processed
	const PASSED          = 20000000;
	const PASSED_DEFAULT  = 20000000; // accepted without compiling
	const PASSED_COMPARE  = 21000000; // + #of test cases attempted
	const FAILED          = 30000000;
	const FAILED_LANGUAGE = 31000000;
	const FAILED_COMPILE  = 32000000;
	const FAILED_RUN      = 33000000; // + #of test cases attempted
	const FAILED_COMPARE  = 34000000; // + #of test cases attempted
	
	// one of the constants
	static function base_status($status) {
		return intval($status / 1000000) * 1000000;
	}
	// one of NOT_DONE, PENDING, PASSED, FAILED
	static function base_status_group($status) {
		return intval($status / 10000000) * 10000000;
	}
	
	// Is it a passed status?
	static function is_passed($status) {
		return Status::base_status_group($status) == Status::PASSED;
	}
	// Is it a failed status?
	static function is_failed($status) {
		return Status::base_status_group($status) == Status::FAILED;
	}
	
	
	static function to_text($status) {
		$status = Status::to_status($status);
		switch (Status::base_status($status)) {
			case Status::NOT_DONE:         return "Not submitted";
			case Status::PENDING:          return "Pending";
			case Status::JUDGING:          return "Judging";
			case Status::PASSED_DEFAULT:   return "Passed (without checking)";
			case Status::PASSED_COMPARE:   return "Passed";
			case Status::FAILED:           return "Failed";
			case Status::FAILED_LANGUAGE:  return "Failed: unknown language";
			case Status::FAILED_COMPILE:   return "Failed: compile error";
			case Status::FAILED_RUN:       return "Failed: runtime error";
			case Status::FAILED_COMPARE:   return "Failed: wrong answer";
			default:                       return "Unknown status";
		}
	}
	static function to_short_text($status) {
		$status = Status::to_status($status);
		switch (Status::base_status_group($status)) {
			case Status::NOT_DONE:         return "none";
			case Status::PENDING:          return "pending";
			case Status::PASSED:           return "pass";
			case Status::FAILED:           return "fail";
			default:                       return "?";
		}
	}
	
	static function to_css_class($status) {
		$status = Status::to_status($status);
		switch (Status::base_status_group($status)) {
			case Status::NOT_DONE:         return "no-submission";
			case Status::PENDING:          return "pending";
			case Status::JUDGING:          return "judging";
			case Status::PASSED:           return "passed";
			case Status::FAILED:           return "failed";
			default:                       return "unknown-status";
		}
	}
	
	static function to_status($it) {
		if ($it === false)  return Status::NOT_DONE;
		if (is_object($it)) return $it->status();
		else                return $it;
	}
}
