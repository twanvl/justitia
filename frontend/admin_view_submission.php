<?php

require_once('../lib/bootstrap.inc');
require_once('./submission_view.inc');

// -----------------------------------------------------------------------------
// View a single submission
// -----------------------------------------------------------------------------

class Page extends PageWithEntity {
	private $subm;
	
	function __construct() {
		Authentication::require_admin();
		$this->is_admin_page = true;
		// find submission
		$this->subm = Submission::by_id(intval($_REQUEST['submissionid']));
		$this->entity = $this->subm->entity();
	}
	
	function title() {
		return "View submissions " . $this->subm->submissionid;
	}
	
	function write_body() {
		if (isset($_REQUEST['redirect'])) {
			echo '<a href="'.htmlspecialchars($_REQUEST['redirect']).'">&larr; back</a>';
		}
		$this->write_block_begin(
			"Submission",
			'block submission ' . Status::to_css_class($this->subm)
		);
		write_submission($this->subm,$this->entity,true);
		$this->write_block_end();
	}
}

$page = new Page();
$page->write();
