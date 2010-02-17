<?php

// -----------------------------------------------------------------------------
// Submission handling
// -----------------------------------------------------------------------------

function file_upload_error_message($error_code) {
	switch ($error_code) {
		case UPLOAD_ERR_INI_SIZE:
			return 'The uploaded file is too large';
		case UPLOAD_ERR_FORM_SIZE:
			return 'The uploaded file is too large';
		case UPLOAD_ERR_PARTIAL:
			return 'The file was only partially uploaded';
		case UPLOAD_ERR_NO_FILE:
			return 'No file was uploaded';
		case UPLOAD_ERR_NO_TMP_DIR:
			return 'Missing a temporary folder';
		case UPLOAD_ERR_CANT_WRITE:
			return 'Failed to write file to disk';
		case UPLOAD_ERR_EXTENSION:
			return 'File upload stopped by extension';
		default:
			return 'Unknown upload error';
	}
}

function handle_uploaded_submission($entity) {
	// is there an upload?
	if (!isset($_FILES['file'])) {
		return false;
	}
	$file = $_FILES['file'];
	
	// is upload okay?
	if (!$entity->submitable()) {
		Template::add_message('submit','error', "No submissions can be made here.");
		@unlink($file['tmp_name']);
		return false;
	}
	if (!$entity->active()) {
		Template::add_message('submit','error', "The deadline has passed for this assignment.");
		@unlink($file['tmp_name']);
		return false;
	}
	if ($file['error'] != UPLOAD_ERR_OK) {
		Template::add_message('submit','error', file_upload_error_message($file['error']));
		@unlink($file['tmp_name']);
		return false;
	}
	
	// is the group ok?
	$group = UserGroup::current();
	$max_size = intval($entity->attribute('max group size'));
	if (count($group) > $max_size) {
		Template::add_message('submit','error', "There are too many students in this group, please remove someone.");
		@unlink($file['tmp_name']);
		return false;
	}
	
	// match filename with regex
	$file_regex = $entity->attribute('filename regex');
	if ($file_regex != '') {
		if (!preg_match("/$file_regex/", $file['name'])) {
			add_message('submit','error', "Uploaded file does not match specified filename pattern");
			return false;
		}
	}
	
	// add to database
	$filename = str_replace('/','',$file['name']);
	$subm = Submission::make_new($entity, $filename);
	
	// add data
	$subm->put_file($subm->code_filename($filename), file_get_contents($file['tmp_name']));
	unlink($file['tmp_name']);
	
	// assign users
	foreach ($group as $user) {
		$subm->add_user($user);
	}
	
	// finalize
	$subm->set_status(Status::PENDING);
	
	// success
	Template::add_message('submit-confirm','confirm', 'Submission received, it will be judged shortly.');
	return $subm;
}
