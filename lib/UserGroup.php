<?php

// -----------------------------------------------------------------------------
// A group of users
// -----------------------------------------------------------------------------

function array_remove(&$array, $value) {
	foreach($array as $k => $v) {
		if ($v == $value) {
			unset($array[$k]);
		}
	}
	print_r($array);
}

class UserGroup {
	private static $current;
	
	static function current() {
		if (!isset(UserGroup::$current)) {
			UserGroup::init();
		}
		return UserGroup::$current;
	}
	
	static function add($user) {
		UserGroup::init();
		// already in group?
		if (!$user) return;
		if (in_array($user, UserGroup::$current)) return;
		
		// update variable
		UserGroup::$current []= $user;
		// update session
		$_SESSION['usergroup'] []= $user->userid;
	}
	
	static function remove($user) {
		if ($user->userid == Authentication::current_user()->userid) return;
		array_remove(UserGroup::$current,    $user);
		array_remove($_SESSION['usergroup'], $user->userid);
	}
	
	static function contains($user) {
		UserGroup::init();
		return in_array($user, UserGroup::$current);
	}
	
	static function add_id($userid) {
		UserGroup::start_session();
		// already in group?
		if (in_array($userid, $_SESSION['usergroup'])) return;
		if ($userid == Authentication::current_user()->userid) return;
		// add
		$_SESSION['usergroup'] []= $userid;
	}
	static function remove_id($userid) {
		UserGroup::start_session();
		array_remove($_SESSION['usergroup'], $userid);
	}
	
	private static function init() {
		// current user is always in group
		$user = Authentication::current_user();
		if (!$user) {
			UserGroup::$current = array();
		} else {
			UserGroup::$current = array($user);
		}
		
		// get from session
		UserGroup::start_session();
		foreach($_SESSION['usergroup'] as $extra_userid) {
			UserGroup::$current []= User::by_id($extra_userid);
		}
	}
	
	private static function start_session() {
		Authentication::session_start();
		if (!isset($_SESSION['usergroup'])) {
			// not set, initialize
			$_SESSION['usergroup'] = array();
		}
	}
}
