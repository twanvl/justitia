<?php

require_once('../lib/bootstrap.inc');
require_once('./submission_view.inc');

// -----------------------------------------------------------------------------
// Ajax utility: view a submission for autorefresh
// -----------------------------------------------------------------------------

$submissionid = intval(@$_REQUEST['submissionid']);
$subm = Submission::by_id($submissionid);
$write_block = (isset($_GET['write_block']) AND ($_GET['write_block']=="true"));

// check access rights
$user =Authentication:: require_user();
if (!$user->is_admin && !$subm->is_made_by($user)) {
	die("You have no rights to view this submission");
}

if($write_block) {
	$entity = $subm->entity();
	Template::write_block_begin(
	$subm->submissionid . ': ' . $entity->title(), 'collapsable block submission ' . Status::to_css_class($subm), '', 'submission-'.$subm->submissionid
	);
	
	write_submission($subm);
	
	Template::write_block_end();
} else {
	echo "<div class=\"newstatus ", Status::to_css_class($subm), "\"></div>";
	write_submission($subm);
}