<?php

// -----------------------------------------------------------------------------
// Page template
// -----------------------------------------------------------------------------

abstract class Template {
	// ---------------------------------------------------------------------
	// Overloadable interface
	// ---------------------------------------------------------------------
	
	protected $auto_refresh = 0;
	protected $auto_refresh_to;
	protected $is_admin_page = false;
	
	abstract function title();
	abstract function write_body();
	
	// ---------------------------------------------------------------------
	// Message utilities
	// ---------------------------------------------------------------------
	
	static $messages;
	function add_message($what,$good,$msg) {
		global $messages;
		if (!isset($messages))        $messages = array();
		if (!isset($messages[$what])) $messages[$what] = array();
		$messages[$what][] = array('good'=>$good, 'msg'=>$msg);
	}
	function write_messages($what) {
		global $messages;
		if (!isset($messages,$messages[$what])) return;
		foreach($messages[$what] as $it) {
			echo '<div class="'.$it['good'].'-message">'.$it['msg'].'</div>';
		}
		unset($messages[$what]);
	}
	function has_messages($what) {
		global $messages;
		return isset($messages,$messages[$what]);
	}
	
	// ---------------------------------------------------------------------
	// Form utilities
	// ---------------------------------------------------------------------
	
	function write_form_begin($url, $method, $enctype = false) {
		echo '<form action="'.$url.'" method="'.$method.'"';
		if ($enctype) {
			echo ' enctype="multipart/form-data"';
		}
		echo ">\n";
	}
	function write_form_end($submit) {
		echo '<input type="submit" value="'.$submit.'">'."\n";
		echo '</form>'."\n";
	}
	
	function write_form_hidden($name,$value) {
		echo '<input type="hidden" name="'.$name.'" value="'. htmlspecialchars($value) .'">';
	}
	function write_form_preserve($what) {
		if (!isset($_REQUEST[$what])) return;
		$this->write_form_hidden($what,$_REQUEST[$what]);
	}
	
	function write_form_table_begin() {
		echo "<table>";
	}
	function write_form_table_end() {
		echo "</table>";
	}
	
	function write_form_field($type, $name, $value = null, $extra = null) {
		if ($type == 'checkbox' || $type == 'radio') {
			$valuespec = ($value ? ' checked="checked"' : '');
		} else {
			$valuespec = $value === null ? '' : ' value="'. htmlspecialchars($value) . '"';
		}
		echo "<input type=\"$type\" id=\"$name\" name=\"$name\"$valuespec$extra>";
	}
	function write_form_table_field($type, $name, $label, $value = null, $extra = null) {
		if ($type == 'checkbox' || $type == 'radio') {
			echo "<tr><td></td><td><label>";
			$this->write_form_field($type, $name, $value, $extra);
			echo " $label</label></td></tr>\n";
		} else {
			echo "<tr><td><label for=\"$name\">$label</label></td><td>\n";
			$this->write_form_field($type, $name, $value, $extra);
			echo "</td></tr>\n";
		}
	}
	
	// ---------------------------------------------------------------------
	// Blocks
	// ---------------------------------------------------------------------
	
	function write_block_begin($title, $class='block', $url='') {
		echo "<div class=\"$class\">";
		$titleHTML = htmlspecialchars($title);
		if ($url) {
			$titleHTML = '<a href="'.htmlspecialchars($url).'">'.$titleHTML.'</a>';
		} else {
			$titleHTML = "<span>".$titleHTML."</span>";
		}
		echo "<div class=\"title\">".$titleHTML."</div>";
		echo "<div class=\"content\">";
	}
	function write_block_end() {
		echo "</div></div>";
	}
	
	// ---------------------------------------------------------------------
	// Navigation
	// ---------------------------------------------------------------------
	
	function write_admin_nav() {
		if (!Authentication::is_admin()) return;
		echo '<div id="admin-nav">';
		$admin_nav_item = array(
			array(
				'title'   => 'Normal view',
				'url'     => 'index.php' . @$_SERVER['PATH_INFO'],
				'current' => Util::current_script_is('index.php')
			),
			array(
				'title'   => 'Users',
				'url'     => 'admin_user.php' . @$_SERVER['PATH_INFO'],
				'current' => Util::current_script_is('admin_user.php')
			),
			array(
				'title'   => 'Results table',
				'url'     => 'admin_results.php' . @$_SERVER['PATH_INFO'],
				'current' => Util::current_script_is('admin_results.php')
			),
			array(
				'title'   => 'Print submissions',
				'url'     => 'admin_print.php' . @$_SERVER['PATH_INFO'],
				'current' => Util::current_script_is('admin_print.php')
			),
		);
		$first = true;
		foreach ($admin_nav_item as $i) {
			if (!$first) echo ' | ';
			$first = false;
			echo "<a href=\"$i[url]\"".($i['current'] ? ' class="current"': '').">$i[title]</a>";
		}
		echo '</div>';
	}
	// ---------------------------------------------------------------------
	// Navigation
	// ---------------------------------------------------------------------
	
	function get_nav() {
		return array();
	}
	
	function write_nav_item($items) {
		if (count($items) == 0) return;
		echo "<li><ul>";
		foreach ($items as $i) {
			echo '<li><a href="'. $i['url'] .'"'
				. (@$i['class'] ? ' class="'.$i['class'].'"' : '')
				. '>'
				. htmlspecialchars($i['title']) . '</a></li>';
		}
		echo "</ul></li>";
	}
	function write_nav() {
		$items = $this->get_nav();
		if (count($items) == 0) return;
		echo '<ul id="nav">';
		foreach ($items as $item) {
			$this->write_nav_item($item);
		}
		echo '</ul>';
	}
	
	// ---------------------------------------------------------------------
	// Writing
	// ---------------------------------------------------------------------
	
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
    <title><?php echo $title; ?> - Justitia</title>
    <link rel="stylesheet" type="text/css" href="<?php echo $base; ?>style/style.css">
    <link rel="stylesheet" type="text/css" href="<?php echo $base; ?>style/jquery.autocomplete.css">
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
    <script type="text/javascript" src="<?php echo $base; ?>style/jquery.autocomplete.js"></script>
    <script type="text/javascript" src="<?php echo $base; ?>style/script.js"></script>
    <?php
	if (false && $this->auto_refresh) {
		$url = isset($this->auto_refresh_to) ? ";url=".htmlspecialchars($base . $this->auto_refresh_to) : "";
		echo '<meta http-equiv="refresh" content="'.$this->auto_refresh.$url.'">';
	}
    ?>
    <base href="<?php echo $base; ?>">
  </head>
  <body<?php if ($this->is_admin_page) echo ' class="admin"'; ?>>
    <div id="header">
      <div id="appname">Justitia, <small>Programming Judge</small></div>
      <?php $this->write_user_header(); ?>
    </div>
    <?php $this->write_admin_nav(); ?>
    <div id="nav-wrap">
      <?php $this->write_nav(); ?>
    </div>
    <div id="main">
      <h1><?php echo $title; ?></h1>
      <?php $this->write_body(); ?>
    </div>
  </body>
</html>
<?php
	}
}
