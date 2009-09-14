<?php

require_once('../lib/bootstrap.inc');
require_once('./submission_view.inc');

// -----------------------------------------------------------------------------
// View a multiple submissions
// -----------------------------------------------------------------------------

class Page extends PageWithEntity {
	private $user;
	
	function __construct() {
		Authentication::require_admin();
		$this->is_admin_page = true;
		// find active entity
		parent::__construct();
		// find user
		try {
			$this->user = User::by_id(intval($_REQUEST['userid']));
		} catch (Exception $e) {
			ErrorPage::die_fancy($e->getMessage());
		}
	}
	
	function title() {
		return "Submission by " . htmlspecialchars($this->user->name());
	}
	
	function write_body() {
		echo '<a href="admin_results.php'.htmlspecialchars($this->entity->path()).'">view all users</a>';
		// list submissions
		$submissions = $this->user->submissions_to($this->entity);
		if (empty($submissions)) {
			echo "<em>no submissions have been made for this assignment by this user.</em>";
		} else {
			$i = count($submissions);
			foreach($submissions as $subm) {
				// is this an interesting submission?
				$is_interesting = true;
				if ($is_interesting) $last_submission = $subm;
				// write
				$this->write_block_begin(
					'Submission '. $i,
					'collapsable block submission '
					 . ($is_interesting ? '' : 'collapsed ')
					 . Status::to_css_class($subm)
				);
				write_submission($subm,$this->entity);
				$this->write_block_end();
				$i--;
			}
		}
	}
}

$page = new Page();
$page->write();
