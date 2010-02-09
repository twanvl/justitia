<?php

require_once('../lib/bootstrap.inc');
require_once('./submission_view.inc');

// -----------------------------------------------------------------------------
// View all submissions made by a SINGLE user to a SINGLE entity
// 
//   url: admin_user_submission.php/path/to/entity?userid=$USERID
// 
// -----------------------------------------------------------------------------

class View extends PageWithEntity {
	private $user;
	
	function __construct() {
		Authentication::require_admin();
		$this->is_admin_page = true;
		// find active entity
		parent::__construct();
		// find user
		if (!isset($_REQUEST['userid'])) throw new NotFoundException("Missing parameter: userid");
		$this->user = User::by_id(intval($_REQUEST['userid']));
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
					 , ''
					 , "submission-$subm->submissionid"
				);
				write_submission($subm,$this->entity);
				$this->write_block_end();
				$i--;
			}
		}
	}
}

$view = new View();
$view->write();
