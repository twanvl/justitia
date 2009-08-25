<?php

require_once('./bootstrap.inc');

// -----------------------------------------------------------------------------
// Allow a user do download submitted files
// -----------------------------------------------------------------------------

// Parse arguments
if (!isset($_SERVER['PATH_INFO'])) die("no file specified");
$path = $_SERVER['PATH_INFO'];
if ($path{0} == '/') $path = substr($path,1);
list($submissionid) = explode('/',$path,2); // only the first part

// Find submission
$subm = Submission::by_id($submissionid);
if (!$subm->is_made_by(Authentication::require_user())) {
	die("You have no rights to view this submission.");
}
$filename = $subm->file_path . '/code/' . $subm->file_name;

// Open file and pass it through
if (!file_exists($filename)) die("file not found");
$fp = fopen($filename, "rb");

if (!function_exists('mime_content_type')) {
	// windows doesn't have this
	function mime_content_type() {
		return 'application octet-stream';
	}
}

header("Content-Type: " . mime_content_type($filename));
fpassthru($fp);
