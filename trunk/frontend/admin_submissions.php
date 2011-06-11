<?php

require_once('../lib/bootstrap.inc');
require_once('./submission_view.inc');

// -----------------------------------------------------------------------------
// The latest submissions
// -----------------------------------------------------------------------------

class View extends PageWithEntity {
	function __construct() {
		// find active entity
		parent::__construct();
		Authentication::require_admin();
		$this->is_admin_page = true;
	}
	
	function title() {
		return "Latest submissions";
	}
	
	function write_body() {
		// TODO: filters?
		$this->write_submissions();
	}
	
	function write_submissions() {
		$start = isset($_REQUEST['start']) ? (int)$_REQUEST['start'] : 0;
		$subms = $this->entity->latest_submissions($start, 10);
		foreach ($subms as $subm) {
			$entity = $subm->entity();
			$this->write_block_begin(
				$subm->submissionid . ': ' . $entity->title(),
				'collapsable block submission ' . Status::to_css_class($subm)
			);
			write_submission($subm,$entity,true);
			$this->write_block_end();
		}
		if ($start > 0) echo '<a href="admin_submissions.php?start='.($start-10).'">&larr; prev</a> | ';
		echo '<a href="admin_submissions.php?start='.($start+10).'">next &rarr;</a>';
	}
	
	// ---------------------------------------------------------------------
	// Navigation
	// ---------------------------------------------------------------------
	
	// which script to use for items from the navigation tree
	function nav_script($entity) {
		return 'admin_submissions.php';
	}
	
}

$view = new View();
$view->write();
