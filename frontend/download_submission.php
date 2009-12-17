<?php

require_once('../lib/bootstrap.inc');

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
		return array(true,$subm->code_filename());
	} else if ($dir == 'out') {
		$ext = pathinfo($filename, PATHINFO_EXTENSION);
		$base = pathinfo($filename, PATHINFO_FILENAME);
		if ($filename == 'compiler.err')          $ok = $entity->show_compile_errors()          || Authentication::is_admin();
		else if ($ext == 'err')                   $ok = $entity->show_runtime_errors_for($base) || Authentication::is_admin();
		else if ($ext == 'out' || $ext == 'diff') $ok = $entity->show_input_output_for($base)   || Authentication::is_admin();
		else                                      $ok = false;
		if ($ok) {
			return array(true,$subm->output_filename($filename));
		}
	} else if ($dir == 'in') {
		$ext  = pathinfo($filename, PATHINFO_EXTENSION);
		$base = pathinfo($filename, PATHINFO_FILENAME);
		if      ($ext == 'desc')                $ok = true;
		else if ($ext == 'in' || $ext == 'out') $ok = $entity->show_input_output_for($base) || Authentication::is_admin();
		else                                    $ok = false;
		if ($ok) {
			return array(false,$subm->input_filename($filename));
		}
	}
	return false; // unknown file
}
function content_type($filename) {
	$ext = pathinfo($filename, PATHINFO_EXTENSION);
	$lang = Util::language_from_filename($filename);
	if ($ext == 'diff') {
		// TODO: determine whether this is a diff in HTML format
		return 'text/html';
	} else if ($ext == 'in' || $ext == 'out' || $ext == 'diff' || $ext == 'err') {
		return 'text/plain';
	} else if (function_exists('mime_content_type')) {
		return mime_content_type($filename);
	} else if ($lang['is_language']) {
		return 'text/plain';
	} else {
		return 'application/octet-stream';
	}
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
$fileinfo = is_allowed_file($subm,$subm->entity(),$user, $sub_dir,$sub_file);
if ($fileinfo === false) {
	die("You have no rights to view this file.");
} else {
	list($in_db,$filename) = $fileinfo;
}

header("Content-Type: " . mime_content_type($sub_file));

if ($in_db) {
	// get the file from the database
	$file = $subm->get_file($filename);
	if ($file === false) {
		die("file not found: $sub_dir/$sub_file");
	}
	echo $file;
	
} else {
	// Open file and pass it through
	if (!file_exists($filename)) {
		die("file not found: $sub_dir/$sub_file");
	}
	$fp = fopen($filename, "rb");

	fpassthru($fp);
}
