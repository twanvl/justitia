<?php

require_once('../lib/bootstrap.inc');

// -----------------------------------------------------------------------------
// Documentation viewer
// The documentation is loaded from files in ../doc
// -----------------------------------------------------------------------------

define('DOC_DIR','../doc');

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
		// default path
		if ($path=='' || $path=='/') $path = '/user';
		// does the file exist?
		if (file_exists(DOC_DIR . $path . ".html")) {
			$this->read_file($path);
		} elseif (file_exists(DOC_DIR . $path . "/index.html")) {
			$this->read_file("$path/index");
		} else {
			throw new NotFoundException("File not found: $path");
		}
	}
	
	function read_file($path) {
		$filename = DOC_DIR . $path . ".html";
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
			$this->nav_item('Writing courses', '/courses'),
			$this->nav_item('Results & grading', '/results'),
			$this->nav_item('Administration', '/admin'),
			$this->nav_item('Program design', '/design'),
		);
		if ($this->is_path_prefix('/user'))
		$result [] = array(
			$this->nav_item('What is Justitia?', '/user/index'),
			$this->nav_item('Logging in', '/user/login'),
			$this->nav_item('Submitting programs', '/user/submitting'),
			$this->nav_item('Feedback explained', '/user/feedback'),
		);
		if ($this->is_path_prefix('/courses'))
		$result [] = array(
			$this->nav_item('Introduction', '/courses/index'),
			$this->nav_item('Test cases', '/courses/test_cases'),
			$this->nav_item('Attribute reference', '/courses/attributes'),
			$this->nav_item('Language notes', '/courses/languages'),
			$this->nav_item('FAQ / HOWTO', '/courses/howto'),
			$this->nav_item('Example', '/courses/example'),
		);
		if ($this->is_path_prefix('/results'))
		$result [] = array(
			$this->nav_item('Introduction', '/results/index'),
			$this->nav_item('Viewing submissions', '/results/submissions'),
			$this->nav_item('Result table', '/results/results'),
			$this->nav_item('Printing and exporting', '/results/export')
		);
		if ($this->is_path_prefix('/admin'))
		$result [] = array(
			$this->nav_item('Introduction', '/admin/index'),
			$this->nav_item('Installation', '/admin/installation'),
			$this->nav_item('Judge daemons', '/admin/daemons'),
			$this->nav_item('User administration', '/admin/users'),
			$this->nav_item('Bugs / issues / features', '/admin/bugs'),
		);
		if ($this->is_path_prefix('/design'))
		$result [] = array(
			$this->nav_item('Design overview', '/design/index'),
			$this->nav_item('Classes', '/design/classes'),
			$this->nav_item('Security considerations', '/design/security')
		);
		return $result;
	}
}

$view = new View();
$view->write();
