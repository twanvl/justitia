<?php

require_once('./bootstrap.inc');

// -----------------------------------------------------------------------------
// Allow a user do download submitted files
// -----------------------------------------------------------------------------

function is_valid_file($filename) {
	return strpos($filename,'..')===false;
}

// Parse arguments
if (!isset($_SERVER['PATH_INFO'])) die("no file specified");
$path = $_SERVER['PATH_INFO'];
if ($path{0} == '/') $path = substr($path,1);
list($submissionid, $sub_file) = explode('/',$path,2); // only the first part

if (!is_valid_file($sub_file)) {
	die("You have no rights to view this file.");
}

// Find submission
$subm = Submission::by_id($submissionid);
$user = Authentication::require_user();
if (!$user->is_admin && !$subm->is_made_by($user)) {
	die("You have no rights to view this submission.");
}
$filename = $subm->file_path . '/' . $sub_file;

// Open file and pass it through
if (!file_exists($filename)) die("file not found: $filename");
$fp = fopen($filename, "rb");

if (!function_exists('mime_content_type')) {
	// windows doesn't have this
	function mime_content_type() {
		return 'application octet-stream';
	}
}

header("Content-Type: " . mime_content_type($filename));
fpassthru($fp);
