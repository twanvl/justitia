<?php

require_once('./template.inc');

$page = new OutputPage();
$page->title = "Welcome";
$page->show();


echo "Welcome to the Apollo programming assigment verification system";

$ignore= <<<EOF

<ul>
 <li>Home
 <li>User settings
 <li>Course X
   <ul>
     <li>Quick overview
     <li>Problem X
      <ul>
        <li>Submit
        <li>Submission X details
      </ul>
   </ul>
</ul>

Admin interface
<ul>
 <li>Config overview
 <li>Users
   <ul>
     <li>List
     <li>Find
     <li>Add user(s)
   </ul>
 <li>Courses
   <ul>
     <li>Course X
      <ul>
        <li>Rescan (when problem set has changed)
        <li>Users overview
        <li>Submissions overview
        <li>Problem X
         <ul>
           <li>Users overview
           <li>Submissions overview
            <ul>
              <li>Submission X details
            </ul>
         </ul>
      </ul>
   </ul>
</ul>


EOF;

// scan problems/courses
$course_dir = '../../courses';

class DirectoryWalker {
	var $_path;
	var $_dir;
	var $_subdirs;
	var $_info;
	
	function DirectoryWalker($path) {
		$this->_path = $path;
		$this->_dir  = dir($path);
	}
	
	function subdirs() {
	}
	
	function info() {
	}
};

// -----------------------------------------------------------------------------
// Ranges of dates/times
// -----------------------------------------------------------------------------

function parse_date($date_str, $rel=NULL) {
	if (is_int($date_str))     return $date_str; // was already a timestamp
	if ($date_str == 'always') return 0;
	if ($date_str == 'never')  return (float)'INF';
	else                       return strtotime($date_str, $rel);
}

class DateRange {
	// start/end timestamps
	var $start;
	var $end;
	
	function __construct($start_str, $end_str) {
		$this->start = parse_date($start_str);
		$this->end   = parse_date($end_str);
	}
	
	// Does this range contain the given time?
	function contains($date) {
		return $this->start <= $date && $date < $this->end;
	}
	
	// Does this range contain the current time?
	function contains_now() {
		return $this->contains(now());
	}
}

// -----------------------------------------------------------------------------
// Entities
//  An entity is either a problem or a directory of entities
// -----------------------------------------------------------------------------

class Entity {
	private $_parent;     // parent entity
	private $_dir_name;   // dir name (relative to parent)
	private $_path;       // full path (relative to data directory), ends in '/'
	private $_attributes; // key=>value pairs specified in "dir.conf" or NULL if not initialized
	private $_children;   // children array or NULL if not initialized
	
	// the root entity
	static function get_root() {
		static $root;
		if (!isset($root)) $root = new Entity(NULL,'');
		return $root;
	}
	// singleton constructor for Entities
	static function get($path) {
		$parts = explode('/',$path);
		$here  = get_root();
		foreach ($parts as $part) {
			if ($part == '') continue;
			$here = $here->get_child($part);
			if ($here === NULL) {
				die("Internal error: Entity not found: $path");
				return NULL;
			}
		}
	}
	
	private function __construct($parent, $dir_name) {
		$parent_path = isset($parent) ? $parent->_path : '';
		$this->_parent   = $parent;
		$this->_dir_name = $dir_name;
		$this->_path     = $parent_path . $dir_name . '/';
	}
	
	// gives an array of child entities
	function children() {
		$this->load_children();
		return $this->_children;
	}
	// gets a single child entity, if it exists (null otherwise)
	function get_child($name) {
		$this->load_children(); // a bit overkill
		return isset($this->_children[$name]) ? $this->_children[$name] : NULL;
	}
	
	// gets a spcific attribute
	function attribute($key, $default = NULL) {
		$this->load_attributes();
		if (!array_key_exists($key, $this->_attributes) && isset($this->_parent)) {
			// if it is not set, look in the parent
			// note: key not set != null value
			//       the latter indicates the parent doesn't have the attribute either
			$attr = $this->_parent->attribute($key);
			if ($attr === NULL) {
				$attr = $this->_parent->attribute("child $key");
			}
			$this->_attributes[$key] = $attr;
		} else {
			$attr = $this->_attributes;
		}
		return is_null($attr) ? $default : $attr;
	}
	
	// is this the root?
	function is_root() {
		return $this->_parent === NULL;
	}
	
	
	// load the directory listing of children
	private function load_children() {
		if (isset($this->_children)) return;
		$this->_children = array();
		echo "[",$this->data_path(),"]";
		foreach (new DirectoryIterator($this->data_path()) as $child) {
			if ($child->isDot() || !$child->isDir()) continue;
			// TODO: strip hidden files?
			$this->_children[$child->getFilename()] = new Entity($this, $child->getFilename());
		}
	}
	
	// load the attributes from a file
	private function load_attributes() {
		if (isset($this->_attributes)) return;
		if ($this->_attributes !== NULL) return;
		$lines = @file($this->data_path() . "dir.conf");
		// TODO: parse...
		$this->_attributes = array();
	}
	
	private function data_path() {
		global $course_dir;
		return $course_dir . $this->_path;
	}
	
	// 
};

// -----------------------------------------------------------------------------
// Directory listing
// -----------------------------------------------------------------------------

$base_dir= "../../courses";

$d = dir($base_dir);
$title    = "Available pages";
$contents = "<ul>";
while (($entry = $d->read()) !== false) {
	echo $entry;
	if (is_dir($base_dir . $entry) && !preg_match("@^[.]@",$entry)) {
		if (is_dir($base_dir . $entry . '/manual-label')) {
			$contents .= "<li><a href='label.php?page=$entry'>$entry</a>";
		} else {
			//$contents .= "<li>$entry";
		}
	}
}
$d->close();

//require_once('template.inc');

function write_tree($e) {
	echo "<ul>";
	foreach($e->children() as $n => $d) {
		echo "<li>$n";
		write_tree($d);
		echo "</li>";
	}
	echo "</ul>";
}

write_tree(Entity::get_root());

$e = Entity::get_root();
$dirs = $e->children();
foreach($dirs as $n => $d) {
	echo "[$n]";
	print_r($d->children());
}


