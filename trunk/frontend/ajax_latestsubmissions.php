<?php 
require_once('../lib/bootstrap.inc');

// -----------------------------------------------------------------------------
// Ajax utility: return newest submissions
// -----------------------------------------------------------------------------

if(Authentication::is_admin() AND isset($_GET['entity']) AND isset($_GET['submissionid'])) {
	try {
		// get entity
		$entity = Entity::get($_GET['entity'], false, true);
		$submissions = $entity->submissions_after($_GET['submissionid']);
		$arr = array();
		foreach($submissions as $s) {
			$arr[] = $s->submissionid;
		}
		echo '{"new_ids":'.json_encode($arr).'}';
	} catch(NotFoundException $e) {
		exit();
	}
} else {
	die("You have no rights to view this submission");
}