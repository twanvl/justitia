<?php

require_once('../lib/bootstrap.inc');

// -----------------------------------------------------------------------------
// Print submissions
// -----------------------------------------------------------------------------

class Page extends Template {
	private $entity;
	
	function __construct() {
		Authentication::require_admin();
		// find active entity
		$this->entity = Entity::get(@$_SERVER['PATH_INFO']);
	}
	
	function title() {
		return "Results for " . $this->entity->title();
	}
	
	function write_body() {
		echo "Only for users: [________________]<br>";
		echo "( ) Text output<br>";
		echo "( ) HTML output<br>";
		echo "( ) Latex rendered PDF output<br>";
		echo "    [x] Two sided printing<br>";
		
		echo "<form>";
		$this->write_form_table_field('password','password', 'Password');
		echo '<label><input type="radio" name="format" value="text"> Text output</label>';
		echo '<label><input type="radio" name="format" value="text" checked> HTML output</label>';
		echo '<label><input type="radio" name="format" value="text"> Latex rendered PDF output</label>';
		echo "</form>";
	}
}

$page = new Page();
$page->write();
