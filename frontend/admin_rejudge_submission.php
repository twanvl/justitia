<?php

require_once('../lib/bootstrap.inc');

// -----------------------------------------------------------------------------
// Rejudge a submission
// -----------------------------------------------------------------------------

Authentication::require_admin();

if (isset($_REQUEST['rejudge'])) {
	Submission::rejudge_by_id(intval($_REQUEST['rejudge']));
	if (isset($_REQUEST['redirect'])) {
		Util::redirect($_REQUEST['redirect']);
	} else {
		Util::redirect("admin_view_submission.php?submissionid=" . $_REQUEST['rejudge']);
	}
}
