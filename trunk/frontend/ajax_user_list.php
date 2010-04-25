<?php

require_once('../lib/bootstrap.inc');

// -----------------------------------------------------------------------------
// Ajax utility: return a list of users that match a query pattern
// -----------------------------------------------------------------------------

$pattern = @$_REQUEST['q'];
if (strlen($pattern) < 3) {
	exit();
}
$filter = '%' . $pattern . '%';
$users = User::all($filter);

$max = 6;

foreach($users as $user) {
	if ($max-- == 0) break;
	echo $user->name(),'|',$user->login,'|',$user->userid,"\n";
}
