<?php

// -----------------------------------------------------------------------------
// Date utilities
// -----------------------------------------------------------------------------

function parse_date($date_str, $rel=NULL, $log_info=NULL) {
	if (is_int($date_str))     return $date_str; // was already a timestamp
	if ($date_str == 'always') return 0;
	if ($date_str == 'never')  return 1e100;
	$date = strtotime($date_str, $rel);
	if ($date === false) {
		Log::warning("Parse error in date \"$date_str\"");
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

function format_date_compact($date, $is_deadline = false) {
	// TODO: Something like "today" or "3 minutes ago"
	return format_date($date);
}

// -----------------------------------------------------------------------------
// Ranges of dates/times
// -----------------------------------------------------------------------------

class DateRange {
	// start/end timestamps
	public $start;
	public $end;
	
	function __construct($start_str, $end_str, $log_info=NULL) {
		$this->start = parse_date($start_str, null, $log_info);
		$this->end   = parse_date($end_str, $this->start, $log_info);
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
