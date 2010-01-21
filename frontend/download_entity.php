<?php

require_once('../lib/bootstrap.inc');

// -----------------------------------------------------------------------------
// Allow a user do download 'extra' files for an entity
// -----------------------------------------------------------------------------

$user = Authentication::require_user();
$entity = Entity::get(@$_SERVER['PATH_INFO'], !$user->is_admin);

// Which file are we downloading?
$filename = @$_REQUEST['f'];
$files = $entity->downloadable_files();
if (!in_array($filename,$files)) {
	die("You have no rights to view this file.");
}
$filename = $entity->data_path() . $filename;

// Does the file exist?
if (!file_exists($filename)) {
	die("file not found: $filename");
}

header("Content-Type: " . Util::content_type($filename));

// Open file and pass it through
$fp = fopen($filename, "rb");
fpassthru($fp);
