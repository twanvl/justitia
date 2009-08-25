<?php

require_once('./bootstrap.inc');

// -----------------------------------------------------------------------------
// Logging in
// -----------------------------------------------------------------------------

// Already logged in?
if (Authentication::current_user() !== false) {
	Util::redirect( @$_REQEST['redirect']);
}

// Try to log in
if (isset($_REQUEST['login'],$_REQUEST['password'])) {
	try {
		$user = User::by_login($_REQUEST['login']);
		$user->check_password($_REQUEST['password']);
		Authentication::set_current_user($user);
		Util::redirect(@$_REQEST['redirect']);
	} catch (Exception $e) {
		$error = "Incorrect username or password.";
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
		global $error;

?><?php
    if (isset($error)) {
      echo "<div class=\"error\">$error</div>";
    }
  ?>
  <form action="login.php" method="post">
  <input type="hidden" name="redirect" value="<?php echo htmlspecialchars(@$_REQEST['redirect']) ?>">
  <table>
    <tr><td><label for="login">Login</label></td>
        <td><input type="text" id="login" name="login" value="<?php echo htmlspecialchars(@$_REQUEST['login']) ?>"></td></tr>
    <tr><td><label for="password">Password</label></td>
        <td><input type="password" id="password" name="password" value=""></td></tr>
  </table>
  <input type="submit" value="Log in">
</form><?php

	}
}

new Page();
