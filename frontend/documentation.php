<?php

require_once('../lib/bootstrap.inc');

// -----------------------------------------------------------------------------
// Documentation viewer
// The documentation is loaded from files in ../doc
// -----------------------------------------------------------------------------

class View extends Template {
	private $path;
	private $title;
	private $body;
	
	function title() {
		return $this->title;
	}
	
	function write_body() {
		echo $this->body;
	}
	
	function __construct() {
		// find requested documentation page
		$path = @$_SERVER['PATH_INFO'];
		$path = preg_replace('@[.].*@','',$path);
		if (!empty($this->path) && $path{0} != '/') $path = '/' . $path;
		if (substr($path,-1)=='/') $path = substr($path,0,-1);
		// does the file exist?
		if (file_exists("../doc$path.html")) {
			$this->read_file($path);
		} elseif (file_exists("../doc$path/index.html")) {
			$this->read_file("$path/index");
		} else {
			throw new NotFoundException("File not found: $path");
		}
	}
	
	function read_file($path) {
		$filename = "../doc$path.html";
		$this->path  = $path;
		$this->title = "Documentation";
		$this->body = file_get_contents($filename);
		// find <h1> tag in body
		if (preg_match("@<h1>(.*)</h1>@",$this->body,&$ma,PREG_OFFSET_CAPTURE)) {
			$this->title = $ma[1][0];
			$this->body = substr_replace($this->body,"", $ma[0][1], strlen($ma[0][0]));
		}
	}
	
	// ---------------------------------------------------------------------
	// Navigation: all known documentation pages
	// ---------------------------------------------------------------------
	
	function is_path_prefix($url) {
		return substr($this->path,0,strlen($url)) == $url;
	}
	function nav_item($title, $url) {
		if ($this->path == $url) {
			$class = 'current';
		} else if ($this->is_path_prefix($url)) {
			$class = 'ancestor';
		} else {
			$class = '';
		}
		return array(
			'title' => $title,
			'url'   => "documentation.php$url",
			'class' => $class
		);
	}
	
	function get_nav() {
		$result [] = array(
			$this->nav_item('User documentation', '/user'),
			$this->nav_item('Course administration', '/courses'),
			$this->nav_item('Results & grading', '/results'),
			$this->nav_item('Program design', '/design')
		);
		if ($this->is_path_prefix('/user'))
		$result [] = array(
			$this->nav_item('About Justitia', '/user/index'),
			$this->nav_item('Submitting programs', 'TODO')
		);
		if ($this->is_path_prefix('/courses'))
		$result [] = array(
			$this->nav_item('Introduction', '/courses/index'),
			$this->nav_item('Test cases', '/courses/test_cases'),
			$this->nav_item('Attribute reference', '/courses/attributes'),
			$this->nav_item('Language notes', '/courses/languages'),
			$this->nav_item('Example', '/courses/example')
		);
		if ($this->is_path_prefix('/admin'))
		$result [] = array(
			$this->nav_item('Introduction', 'TODO'),
			$this->nav_item('Users', 'TODO'),
			$this->nav_item('Viewing results', 'TODO')
		);
		if ($this->is_path_prefix('/design'))
		$result [] = array(
			$this->nav_item('Introduction', '/design/index'),
			$this->nav_item('Security considerations', '/design/security')
		);
		return $result;
	}
}

$view = new View();
$view->write();
