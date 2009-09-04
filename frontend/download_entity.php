<?php

require_once('../lib/bootstrap.inc');

// -----------------------------------------------------------------------------
// Allow a user do download 'extra' files for an entity
// -----------------------------------------------------------------------------

$user = Authentication::require_user();
$entity = Entity::get(@$_SERVER['PATH_INFO'], !$user->is_admin);

// Which file are we downloading?
$filename = @$_REQUEST['f'];
$files = explode(' ',$entity->attribute('downloadable files'));
if ($filename == '' || !in_array($filename,$files)) {
	die("You have no rights to view this file.");
}
$filename = $entity->data_path() . $filename;

// Does the file exist?
if (!file_exists($filename)) {
	die("file not found: $filename");
}

// TODO: move to include file
if (!function_exists('mime_content_type')) {
	// windows doesn't have this
	function mime_content_type($filename) {
		$ext = pathinfo($filename, PATHINFO_EXTENSION);
		$lang = Util::language_from_filename($filename);
		if ($ext == 'in' || $ext == 'out' || $ext == 'diff' || $ext == 'err' || $lang['is_language']) {
			return 'text/plain';
		} else {
			return 'application/octet-stream';
		}
	}
}
header("Content-Type: " . mime_content_type($filename));

// Open file and pass it through
$fp = fopen($filename, "rb");
fpassthru($fp);
