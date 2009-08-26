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

function is_allowed_file($subm,$entity,$user,$dir,$filename) {
	if (strpos($dir,'..') !== false) {
		echo "DOTDOT";
		return false; // security
	}
	if (!$user->is_admin && !$subm->is_made_by($user)) {
		return false; // other user's submission
	}
	if ($dir == 'code') {
		// the file send by the user
		return $subm->code_filename();
	} else if ($dir == 'out') {
		$ext = pathinfo($filename, PATHINFO_EXTENSION);
		$base = pathinfo($filename, PATHINFO_FILENAME);
		if ($filename == 'compiler.err')          $ok = $entity->attribute_bool('show compile errors');
		else if ($ext == 'err')                   $ok = is_allowed_testcase($base,$entity->attribute('show run errors'));
		else if ($ext == 'out' || $ext == 'diff') $ok = is_allowed_testcase($base,$entity->attribute('show input/output'));
		else                                      $ok = false;
		if ($ok || $user->is_admin) {
			return $subm->output_filename($filename);
		}
	} else if ($dir == 'in') {
		$ext  = pathinfo($filename, PATHINFO_EXTENSION);
		$base = pathinfo($filename, PATHINFO_FILENAME);
		if      ($ext == 'desc')                $ok = true;
		else if ($ext == 'in' || $ext == 'out') $ok = is_allowed_testcase($base,$entity->attribute('show input/output'));
		else                                    $ok = false;
		if ($ok || $user->is_admin) {
			return $subm->input_filename($filename);
		}
	}
	return false; // unknown file
}

// Parse arguments
if (!isset($_SERVER['PATH_INFO'])) die("no file specified");
$path = $_SERVER['PATH_INFO'];
if ($path{0} == '/') $path = substr($path,1);
list($submissionid, $sub_dir, $sub_file) = explode('/',$path);

// Find submission
$subm = Submission::by_id($submissionid);
$user = Authentication::require_user();

// Which file are we downloading?
$filename = is_allowed_file($subm,$subm->entity(),$user, $sub_dir,$sub_file);
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
