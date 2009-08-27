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
	
	function write_form_hidden($name,$value) {
		echo '<input type="hidden" name="'.$name.'" value="'. htmlspecialchars($value) .'">';
	}
	function write_form_preserve($what) {
		if (!isset($_REQUEST[$what])) return;
		$this->write_form_hidden($what,$_REQUEST[$what]);
	}
	function write_form_table_field($type, $name, $label, $value = null) {
		if ($type == 'checkbox') {
			$valuespec = ($value ? ' checked="checked"' : '');
			echo "<tr><td></td>\n";
			echo "    <td><label><input type=\"$type\" id=\"$name\" name=\"$name\"$valuespec>\n";
			echo "        $label</label>";
			echo "   </td></tr>\n";
		} else {
			$valuespec = ' value="'. htmlspecialchars($value) . '"';
			echo "<tr><td><label for=\"$name\">$label</label></td>\n";
			echo "    <td><input type=\"$type\" id=\"$name\" name=\"$name\"$valuespec></td></tr>\n";
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
	
	function get_nav() {
		$user = Authentication::current_user();
		if (!$user) return array();
		$result = array();
		$result []= array(
			'title' => 'Courses',
			'url'   => 'index.php'
		);
		if ($user->is_admin) {
			$result []= array(
				'title' => 'Users',
				'url'   => 'admin_user.php'
			);
			$result []= array(
				'title' => 'Results',
				'url'   => 'admin_results.php' . @$_SERVER['PATH_INFO']
			);
		}
		return array($result);
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
		echo '<ul id="nav">';
		foreach ($this->get_nav() as $item) {
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
    <title><?php echo $title; ?> - NewAthena</title>
    <link rel="stylesheet" type="text/css" href="<?php echo $base; ?>style/style.css">
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
    <script type="text/javascript" src="<?php echo $base; ?>style/script.js"></script>
    <?php
	if ($this->auto_refresh) {
		$url = isset($this->auto_refresh_to) ? ";url=".htmlspecialchars($base . $this->auto_refresh_to) : "";
		echo '<meta http-equiv="refresh" content="'.$this->auto_refresh.$url.'">';
	}
    ?>
    <base href="<?php echo $base; ?>">
  </head>
  <body>
    <div id="header">
      <div id="appname">NewAthena</div>
      <?php $this->write_user_header(); ?>
    </div>
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
