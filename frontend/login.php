<?php

require_once('../lib/bootstrap.inc');

// -----------------------------------------------------------------------------
// Logging in
// -----------------------------------------------------------------------------

// Already logged in?
if (Authentication::current_user() !== false) {
	Util::redirect( @$_REQUEST['redirect']);
}

// Try to log in
if (isset($_REQUEST['login'],$_REQUEST['password'])) {
	try {
		$user = User::by_login($_REQUEST['login']);
		$user->check_password($_REQUEST['password']);
		Authentication::set_current_user($user);
		Util::redirect(@$_REQUEST['redirect']);
	} catch (InternalException $e) {
		ErrorPage::die_fancy($e);
	} catch (Exception $e) {
		Template::add_message('login','error', "Incorrect username or password.");
	}
}

// -----------------------------------------------------------------------------
// Login page
// -----------------------------------------------------------------------------

class View extends Template {
	function title() {
		return "Log in";
	}
	function write_body() {
		$this->write_messages('login');
		
		$this->write_form_begin("login.php","post");
		$this->write_form_preserve('redirect');
		$this->write_form_table_begin();
		$this->write_form_table_field('login',   'login',    'Login',    @$_REQUEST['login']);
		$this->write_form_table_field('password','password', 'Password');
		$this->write_form_table_end();
		$this->write_form_end("Log in");
	}
}

$view = new View();
$view->write();
