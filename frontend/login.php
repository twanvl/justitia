<?php

require_once('./bootstrap.inc');

if (Authentication::current_user() !== false) {
	Util::redirect( @$_REQEST['redirect']);
}


$redirect = @$_REQEST['redirect'];
$login = @$_REQUEST['login'];
if (isset($_REQUEST['login'],$_REQUEST['password'])) {
	$user = new User($_REQUEST['login']);
	if ($user->check_password($_REQUEST['password'])) {
		Authentication::set_current_user($user);
		Util::redirect(@$_REQEST['redirect']);
	} else {
		$error = "Incorrect username or password.";
	}
}

?>

<form action="login.php" method="post">
  <?php
    if (isset($error)) {
      echo "<div class=\"error\">$error</div>";
    }
  ?>
  <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect) ?>">
  <table>
    <tr><td><label for="login">Login</label></td>
        <td><input type="text" id="login" name="login" value="<?php echo htmlspecialchars($login) ?>"></td></tr>
    <tr><td><label for="password">Password</label></td>
        <td><input type="password" id="password" name="password" value=""></td></tr>
  </table>
  <input type="submit" value="Log in">
</form>
