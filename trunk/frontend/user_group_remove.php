<?php

require_once('../lib/bootstrap.inc');

// -----------------------------------------------------------------------------
// Remove group user and (optionally) redirect
// -----------------------------------------------------------------------------

Authentication::require_user();

if (isset($_REQUEST['remove'])) {
	UserGroup::remove_id(intval($_REQUEST['remove']));
	if (isset($_REQUEST['redirect'])) {
		Util::redirect($_REQUEST['redirect']);
	} else {
		echo "1";
	}
}
