<?php

require_once('./bootstrap.inc');

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
	} catch (Exception $e) {
		Template::add_message('login','error', "Incorrect username or password.");
	}
}

// -----------------------------------------------------------------------------
// Page template
// -----------------------------------------------------------------------------

class Page extends Template {
	function title() {
		return "Log in";
	}
	function write_body() {
		$this->write_messages('login');

?>
  <form action="login.php" method="post">
  <?php $this->write_form_preserve('redirect'); ?>
  <table>
    <?php $this->write_form_table_field('login',   'login',    'Login',    @$_REQUEST['login']); ?>
    <?php $this->write_form_table_field('password','password', 'Password'); ?>
  </table>
  <input type="submit" value="Log in">
</form><?php

	}
}

$page = new Page();
$page->write();
