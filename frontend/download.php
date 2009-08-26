<?php

require_once('./bootstrap.inc');

// -----------------------------------------------------------------------------
// Allow a user do download submitted files
// -----------------------------------------------------------------------------

function is_allowed_testcase($case, $pattern) {
	if ($pattern == 'all')  return true;
	if ($pattern == 'none') return false;
	if (in_array($case,explode(' ',$pattern))) return true;
	return false;
}

function is_allowed_file($subm,$entity,$user,$filename) {
	if (strpos($filename,'..') !== false) {
		echo "DOTDOT";
		return false; // security
	}
	if (!$user->is_admin && !$subm->is_made_by($user)) {
		return false; // other user's submission
	}
	if ($filename == 'code/' . $subm->file_name) {
		// the file send by the user
		return $subm->file_path . '/' . $filename;
	} else if ($filename == 'out/compiler.err') {
		// compile error
		if ($user->is_admin || $entity->attribute_bool('show compile errors')) {
			return $subm->file_path . '/' . $filename;
		}
	} else if (preg_match("@^out/(([^/]*?)\.err)$@",$filename,$matches)) {
		// runtime error
		if ($user->is_admin || is_allowed_testcase($matches[2],$entity->attribute('show run errors'))) {
			return $subm->file_path . '/' . $filename;
		}
	} else if (preg_match("@^out/(([^/]*?)\.(out|diff))$@",$filename,$matches)) {
		// output/diff
		if ($user->is_admin || is_allowed_testcase($matches[2],$entity->attribute('show input/output'))) {
			return $subm->file_path . '/' . $filename;
		}
	} else if (preg_match("@^in/(([^/]*?)\.(in|out))$@",$filename,$matches)) {
		// input/expected output
		if ($user->is_admin || is_allowed_testcase($matches[2],$entity->attribute('show input/output'))) {
			return COURSE_DIR . $subm->entity_path . $matches[1];
		}
	}
	return false; // unknown file
}

// Parse arguments
if (!isset($_SERVER['PATH_INFO'])) die("no file specified");
$path = $_SERVER['PATH_INFO'];
if ($path{0} == '/') $path = substr($path,1);
list($submissionid, $sub_file) = explode('/',$path,2); // only the first part

// Find submission
$subm = Submission::by_id($submissionid);
$user = Authentication::require_user();

// Which file are we downloading?
$filename = is_allowed_file($subm,$subm->entity(),$user,$sub_file);
if ($filename === false) {
	die("You have no rights to view this file.");
}

// Open file and pass it through
if (!file_exists($filename)) {
	//die("file not found: $submissionid/$sub_file");
	die("file not found: $filename");
}
$fp = fopen($filename, "rb");

if (!function_exists('mime_content_type')) {
	// windows doesn't have this
	function mime_content_type() {
		return 'application octet-stream';
	}
}

header("Content-Type: " . mime_content_type($filename));
fpassthru($fp);
