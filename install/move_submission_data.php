<?php
require_once('../lib/bootstrap.inc');

/**
 * Scripts that can move submission files from the database to the filesystem and back
 */

function move($entity, $submission_path) {
	copy_submissions_for_entity($entity, $submission_path.$entity->path());
	foreach($entity->children() as $child) {
		move($child, $submission_path);	
	}
}

function copy_submissions_for_entity($entity, $path) {
	print($path."\n");
	if(!file_exists($path)) {
		mkdir($path, 0700, true);
	}
	DB::prepare_query($query, "SELECT * FROM `submission` as s JOIN `file` as f ON s.submissionid = f.submissionid WHERE s.`entity_path` = ?");
	$query->execute(array($entity->path()));
	$query->setFetchMode(PDO::FETCH_ASSOC);
	foreach($query->fetchAll() as $row) {
		$filename = $path."submission_".$row['submissionid']."/".$row['filename'];
		create_directory($filename);
		file_put_contents($filename, $row['data']);
	}
	$query->closeCursor();
	
}

function create_directory($path) {
	$dir = implode("/", explode("/", $path, -1));
	if(!file_exists($dir)) { 
		mkdir($dir, 0700, true);
	}
}

$submission_path = substr(SUBMISSION_PATH, -1) == "/" ? substr(SUBMISSION_PATH, 0, -1) : SUBMISSION_PATH;
move(Entity::get_root(), $submission_path);