<?php

class Authentication {
	// Require that a user is logged in
	static function require_user() {
		$user = Authentication::current_user();
		if ($user === false) {
			Util::login();
		}
		return $user;
	}
	static function require_admin() {
		$user = Authentication::require_user();
		if (!$user->is_admin) {
			ErrorPage::die_fancy("Administrators only");
		}
		return $user;
	}
	
	// Get the currently loged in user
	static function current_user() {
		static $current_user;
		if (isset($current_user)) return $current_user;
		
		Authentication::session_start();
		if (isset($_SESSION['login'])) {
			$current_user = User::by_login($_SESSION['login']);
		} else {
			$current_user = false;
		}
		return $current_user;
	}
	static function is_admin() {
		$user = Authentication::current_user();
		return $user && $user->is_admin;
	}
	
	// Set the currently loged in user
	static function set_current_user($u) {
		Authentication::session_start();
		$_SESSION['login'] = $u->login;
	}
	
	static function session_start() {
		static $started = false;
		if ($started) return;
		session_name("NewAthenaSession");
		session_start();
		$started = true;
	}
	
	// Logs the current user out
	static function logout() {
		Authentication::session_start();
		session_unset();
		session_destroy();
		setcookie("NewAthenaSession", "", time()-3600, "/");
	}
	
	// Get the current team used by the user
	static function current_team() {
	}
}
