<?php

// -----------------------------------------------------------------------------
// Page template
// -----------------------------------------------------------------------------

abstract class Template {
	abstract function title();
	abstract function write_body();
	
	// write out the page
	function __destruct() {
		$this->write();
	}
	
	function write_user_header() {
		$user = Authentication::current_user();
		if (!$user) return;
		echo '<div id="user">';
		echo $user->name();
		echo ' (<a href="logout.php">log out</a>)';
		echo "</div>";
	}
	
	function write() {
		$base  = htmlspecialchars(Util::base_url());
		$title = htmlspecialchars($this->title());
		
		header('Content-Type', 'text/html; charset=UTF-8');
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
  <head>
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" type="text/css" href="<?php echo $base; ?>style/style.css">
    <base href="<?php echo $base; ?>">
  </head>
  <body>
    <div id="header">
      NewAthena
      <?php $this->write_user_header(); ?>
    </div>
    <h1><?php echo $title; ?></h1>
    <?php $this->write_body(); ?>
  </body>
</html>
<?php
	}
}
