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
		return false; // security
	}
	if (!$user->is_admin && !$subm->is_made_by($user)) {
		return false; // other user's submission
	}
	if ($dir == 'code') {
		// the file send by the user
		return array(true,$subm->code_filename($filename));
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

// Parse arguments
if (!isset($_SERVER['PATH_INFO'])) die("no file specified");
$path = $_SERVER['PATH_INFO'];
if ($path{0} == '/') $path = substr($path,1);
$path_parts = explode('/',$path,3);
while (count($path_parts) < 3) $path_parts[] = '';
list($submissionid, $sub_dir, $sub_file) = $path_parts;

// Find submission
$subm = Submission::by_id($submissionid);
$user = Authentication::require_user();

// Which file are we downloading?
if ($sub_dir == 'code.zip' && $sub_file == '') {
	// url "<submisasion>/code" points to the entire code submission as a .zip archive
	// temporary name
	$filename = tempnam('','zip');
	$delete_temp_file = $filename;
	// create a zip file
	$zip = new ZipArchive();
	$result = $zip->open($filename,ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
	if ($result !== true) {
		die("Failed to open zip file '$filename', error code $result");
	}
	foreach ($subm->get_code_filenames() as $code_name => $name) {
		$file = $subm->get_file($code_name);
		$zip->addFromString($name, $file);
	}
	$zip->close();
	// download as this name
	$in_db    = false;
	$filetype = 'application/zip';
	$sub_file = "code_" . $subm->submissionid . ".zip";
	header("Content-Disposition: attachment; filename=$sub_file");
} else {
	$fileinfo = is_allowed_file($subm,$subm->entity(),$user, $sub_dir,$sub_file);
	if ($fileinfo === false) {
		die("You have no rights to view this file.");
	} else {
		list($in_db,$filename) = $fileinfo;
	}
}


if ($in_db) {
	// get the file from the database
	$file = $subm->get_file($filename);
	if ($file === false) {
		die("file not found: $sub_dir/$sub_file");
	}
	
	header("Content-Type: " . Util::content_type($sub_file));
	
	echo $file;
	
} else {
	// Open file and pass it through
	if (!file_exists($filename)) {
		die("file not found: $sub_dir/$sub_file");
	}
	
	header("Content-Type: " . Util::content_type($sub_file));
	
	$fp = fopen($filename, "rb");
	fpassthru($fp);
	fclose($fp);
	
	if (isset($delete_temp_file)) {
		@unlink($delete_temp_file);
	}
}
