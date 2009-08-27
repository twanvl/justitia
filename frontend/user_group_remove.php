<?php

require_once('./bootstrap.inc');

// -----------------------------------------------------------------------------
// Remove group user and redirect
// -----------------------------------------------------------------------------

Authentication::require_user();

if (isset($_REQUEST['remove'])) {
	UserGroup::remove_id(intval($_REQUEST['remove']));
	Util::redirect(@$_REQUEST['redirect']);
}
