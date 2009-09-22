<?php

require_once('../lib/bootstrap.inc');
require_once('./submission_view.inc');

// -----------------------------------------------------------------------------
// Ajax utility: view a submission for autorefresh
// -----------------------------------------------------------------------------

$submissionid = intval(@$_REQUEST['submissionid']);
$subm = Submission::by_id($submissionid);

// check access rights
$user =Authentication:: require_user();
if (!$user->is_admin && !$subm->is_made_by($user)) {
	die("You have no rights to view this submission");
}

echo "<div class=\"newstatus ", Status::to_css_class($subm), "\"></div>";
write_submission($subm);
