<?php

// -----------------------------------------------------------------------------
// Authentication:
//  * checking that a user is logged in, and who it is
//  * checking whether it is an administrator
// -----------------------------------------------------------------------------

class Authentication {
	// ---------------------------------------------------------------------
	// The 'current' user
	// ---------------------------------------------------------------------
	
	// Require that a user is logged in
	static function require_user() {
		$user = Authentication::current_user();
		if ($user === false) {
			Authentication::show_login_page();
		}
		return $user;
	}
	static function require_admin() {
		$user = Authentication::require_user();
		if (!$user->is_admin) {
			ErrorPage::die_fancy(new NotAuthorizedException());
		}
		return $user;
	}
	
	// Get the currently loged in user, or return false
	static function current_user() {
		static $current_user;
		if (isset($current_user)) return $current_user;
		
		Authentication::session_start();
		if (isset($_SESSION['userid'])) {
			$current_user = User::by_id($_SESSION['userid']);
		} else {
			$current_user = false;
		}
		return $current_user;
	}
	// Is the current user an admin?
	static function is_admin() {
		$user = Authentication::current_user();
		return $user && $user->is_admin;
	}
	
	// Set the currently loged in user
	static function set_current_user($u) {
		Authentication::session_start();
		$_SESSION['userid'] = $u->userid;
	}
	
	// Logs the current user out
	static function logout() {
		Authentication::session_start();
		session_unset();
		session_destroy();
		setcookie("Justitia", "", time()-3600, "/");
	}
	
	static function session_start() {
		static $started = false;
		if ($started) return;
		session_name("Justitia");
		session_start();
		$started = true;
	}
	
	// Redirects the user to the login page
	static function show_login_page() {
		Util::redirect("login.php?redirect=" . urlencode(Util::current_url()));
	}
	
	// ---------------------------------------------------------------------
	// Password authentication
	// ---------------------------------------------------------------------
	
	// Log in the user based on username/password
	// throws if invalid password
	static function login($login, $pass) {
		// Authenticate using password
		$user = User::by_login($login, false);
		if ($user) {
			$user->check_password($pass);
		} else if (LDAP_CREATE_USER) {
			$user = User::add_from_ldap($login,$pass);
		}
		if (!$user) {
			throw new NotFoundException("User not found: $login");
		}
		// Done
		Authentication::set_current_user($user);
	}
	
}
