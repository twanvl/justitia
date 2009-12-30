<?php

// -----------------------------------------------------------------------------
// Page template
//
//  All HTML pages inherit from Template.
//  They override the title() and write_body() functions
//
//  To write a page, $some_derived_view->write() is invoked.
//
// -----------------------------------------------------------------------------

abstract class Template {
	// ---------------------------------------------------------------------
	// Overloadable interface
	// ---------------------------------------------------------------------
	
	protected $is_admin_page = false; // use the admin stylesheet?
	
	abstract function title();
	abstract function write_body();
	
	// array (layed out horizontally) of arrays (layed out vertically) of navigation items
	// each navigation item is an associative array
	//   array('title'=>, 'url'=>, 'class'=>)
	function get_nav() {
		return array();
	}
	
	// ---------------------------------------------------------------------
	// Message utilities
	//
	//  Template::add_message('something','error','bla bla bla'
	//     queues a message
	//  Template::write_messages('something')
	//     then writes all queued messages. This method is called from write_body()
	// ---------------------------------------------------------------------
	
	private static $messages;
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
	// pass along the value of a request variable using a hidden form field
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
		if ($type == 'textarea') {
			echo "<textarea id=\"$name\" name=\"$name\"$extra>";
			echo htmlspecialchars($value);
			echo "</textarea>";
			return;
		} else if ($type == 'checkbox' || $type == 'radio') {
			$valuespec = ($value ? ' checked="checked"' : '');
		} else {
			$valuespec = $value === null ? '' : ' value="'. htmlspecialchars($value) . '"';
		}
		echo "<input type=\"$type\" id=\"$name\" name=\"$name\"$valuespec$extra>";
	}
	function write_form_table_field($type, $name, $label, $value = null, $extra = null, $pre_label = '') {
		if ($type == 'checkbox' || $type == 'radio') {
			echo "<tr><td>$pre_label</td><td><label>";
			$this->write_form_field($type, $name, $value, $extra);
			echo " $label</label></td></tr>\n";
		} else {
			echo "<tr><td><label for=\"$name\">$label</label></td><td>\n";
			$this->write_form_field($type, $name, $value, $extra);
			echo "</td></tr>\n";
		}
	}
	
	function write_form_table_data($label, $value, $extra = null) {
		echo "<tr><td>$label</td><td>".htmlspecialchars($value)."</td></tr>\n";
	}
	
	// ---------------------------------------------------------------------
	// Blocks
	// ---------------------------------------------------------------------
	
	static function write_block_begin($title, $class='block', $url='', $id='') {
		if ($id) {
			echo "<div class=\"$class\" id=\"$id\">";
		} else {
			echo "<div class=\"$class\">";
		}
		$titleHTML = htmlspecialchars($title);
		if ($url) {
			$titleHTML = '<a href="'.htmlspecialchars($url).'">'.$titleHTML.'</a>';
		} else {
			$titleHTML = "<span>".$titleHTML."</span>";
		}
		echo "<div class=\"title\">".$titleHTML."</div>";
		echo "<div class=\"content\">";
	}
	static function write_block_end() {
		echo "</div></div>";
	}
	
	// ---------------------------------------------------------------------
	// Admin navigation (tab bar)
	// ---------------------------------------------------------------------
	
	function write_admin_nav_link($script,$title,$include_path=true) {
		static $first = true;
		if ($first) {
			$first = false;
		} else {
			echo " | ";
		}
		$current = Util::current_script_is($script) ? ' class="current"' : '';
		$url = htmlspecialchars( $script . ($include_path ? @$_SERVER['PATH_INFO'] : '') );
		echo "<a href=\"$url\"$current>$title</a>";
	}
	function write_admin_nav() {
		if (!Authentication::is_admin()) return;
		// documentation.php paths are not the same as entity paths
		$is_doc = Util::current_script_is('documentation.php');
		echo '<div id="admin-nav">';
		$this->write_admin_nav_link('index.php','Normal view', !$is_doc);
		$this->write_admin_nav_link('admin_user.php','Users', !$is_doc);
		$this->write_admin_nav_link('admin_submissions.php','Latest submissions', !$is_doc);
		$this->write_admin_nav_link('admin_results.php','Results table', !$is_doc);
		$this->write_admin_nav_link('admin_print.php','Print submissions', !$is_doc);
		$this->write_admin_nav_link('documentation.php','Documentation', $is_doc);
		echo '</div>';
	}
	
	// ---------------------------------------------------------------------
	// Navigation
	// ---------------------------------------------------------------------
	
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
		echo ' (<a href="user_settings.php?redirect='.htmlspecialchars(Util::current_url()).'">settings</a>';
		echo ' | <a href="logout.php">log out</a>)';
		echo "</div>";
	}
	
	function app_name() {
		static $app_name;
		if (!$app_name) {
			$user = Authentication::current_user();
			if ($user && $user->login{0} != 's' && (time()/1000)%10 == 0) {
				// just for fun
				$app_name = "Twanthena";
			} else {
				$app_name = "Justitia";
			}
		}
		return $app_name;
	}
	
	function write() {
		Authentication::session_start(); // we need the current user later
		$base  = htmlspecialchars(Util::base_url());
		$title = htmlspecialchars($this->title());
		
		header('Content-Type: text/html; charset=UTF-8');
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
  <head>
    <title><?php echo $title; ?> - <?php echo $this->app_name(); ?></title>
    <link rel="stylesheet" type="text/css" href="<?php echo $base; ?>style/style.css">
    <link rel="stylesheet" type="text/css" href="<?php echo $base; ?>style/jquery.autocomplete.css">
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
    <script type="text/javascript" src="<?php echo $base; ?>style/jquery.autocomplete.js"></script>
    <script type="text/javascript" src="<?php echo $base; ?>style/script.js"></script>
    <base href="<?php echo $base; ?>">
  </head>
  <body<?php if ($this->is_admin_page) echo ' class="admin"'; ?>>
    <div id="header">
      <div id="appname"><?php echo $this->app_name(); ?>, <small>Programming Judge</small></div>
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
	
	function write_print() {
		$base  = htmlspecialchars(Util::base_url());
		$title = htmlspecialchars($this->title());
		header('Content-Type: text/html; charset=UTF-8');
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
  <head>
    <title><?php echo $title; ?> - <?php echo $this->app_name(); ?></title>
    <link rel="stylesheet" type="text/css" href="<?php echo $base; ?>style/print.css">
    <base href="<?php echo $base; ?>">
  </head>
  <body>
    <?php $this->write_print_body(); ?>
  </body>
</html>
<?php
	}
}
