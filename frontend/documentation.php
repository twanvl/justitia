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
	
	function get_nav() {
		$result [] = array(
			array(
				'title' => 'User documentation',
				'url'   => 'documentation.php/user'
			),
			array(
				'title' => 'Course administration',
				'url'   => 'documentation.php/courses'
			),
			array(
				'title' => 'Results & grading',
				'url'   => 'documentation.php/results'
			),
			array(
				'title' => 'Program design',
				'url'   => 'documentation.php/design'
			)
		);
		$result [] = array(
			array(
				'title' => 'About Justitia',
				'url'   => 'documentation.php/user/index'
			),
			array(
				'title' => 'Submitting programs',
				'url'   => 'asdf'
			)
		);
		$result [] = array(
			array(
				'title' => 'Introduction',
				'url'   => 'documentation.php/courses/index'
			),
			array(
				'title' => 'Directory structure',
				'url'   => 'asdf'
			),
			array(
				'title' => 'Test cases',
				'url'   => 'documentation.php/courses/test_cases'
			),
			array(
				'title' => 'Attribute reference',
				'url'   => 'documentation.php/courses/attributes'
			),
			array(
				'title' => 'Example',
				'url'   => 'asdf'
			)
		);
		$result [] = array(
			array(
				'title' => 'Introduction',
				'url'   => 'asdf'
			),
			array(
				'title' => 'Users',
				'url'   => 'asdf'
			),
			array(
				'title' => 'Viewing results',
				'url'   => 'asdf'
			)
		);
		$result [] = array(
			array(
				'title' => 'Introduction',
				'url'   => 'asdf'
			),
			array(
				'title' => 'Security considerations',
				'url'   => 'asdf'
			)
		);
		return $result;
	}
}

$view = new View();
$view->write();
