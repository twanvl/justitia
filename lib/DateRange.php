<?php

// -----------------------------------------------------------------------------
// Date utilities
// -----------------------------------------------------------------------------

function parse_date($date_str, $rel=NULL) {
	if (is_int($date_str))     return $date_str; // was already a timestamp
	if ($date_str == 'always') return 0;
	if ($date_str == 'never')  return 1e100;
	$date = strtotime($date_str, $rel);
	if ($date === false) {
		Log::error("Parse error in date '$date'");
	} else {
		return $date;
	}
}

function now() {
	if (isset($_SERVER['REQUEST_TIME'])) {
		return $_SERVER['REQUEST_TIME'];
	} else {
		return time();
	}
}

date_default_timezone_set(TIMEZONE);

function format_date($date, $is_deadline = false) {
	if ($date >= 1e99) return "never";
	$str = date('l, j F Y, H:i:s',$date);
	if ($is_deadline) {
		// deadline 24:00:00 can not be confused
		if (substr($str,-8) == '00:00:00') {
			$str = date('l, j F Y, ',$date -24*3600) . '24:00:00';
		}
	}
	return $str;
}

// -----------------------------------------------------------------------------
// Ranges of dates/times
// -----------------------------------------------------------------------------

class DateRange {
	// start/end timestamps
	var $start;
	var $end;
	
	function __construct($start_str, $end_str) {
		$this->start = parse_date($start_str);
		$this->end   = parse_date($end_str);
	}
	
	// Does this range contain the given time?
	function contains($date) {
		return $this->start <= $date && $date < $this->end;
	}
	
	// Does this range contain the current time?
	function contains_now() {
		return $this->contains(now());
	}
}
