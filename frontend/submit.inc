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
	if (!$entity->attribute_bool('submitable')) {
		Template::add_message('submit','error', "No submissions can be made here.");
		return false;
	}
	if (!$entity->active()) {
		Template::add_message('submit','error', "The deadline has passed for this assignment.");
		return false;
	}
	if ($file['error'] != UPLOAD_ERR_OK) {
		Template::add_message('submit','error', file_upload_error_message($file['error']));
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
	
	// move file into pending folder
	$subm_dir = Util::create_new_directory(PENDING_DIR, 'submission');
	mkdir($subm_dir . '/code');
	$file_name = str_replace('/','',$file['name']);
	if (!move_uploaded_file($file['tmp_name'], $subm_dir . '/code/' . $file_name)) {
		Template::add_message('submit','error', "Can not move uploaded file");
		rmdir($subm_dir . '/code');
		rmdir($subm_dir);
		return false;
	}
	
	// add to database
	$subm = Submission::make_new($entity, $subm_dir, $file_name);
	// assign users
	foreach (UserGroup::current() as $user) {
		$subm->add_user($user);
	}
	
	// success
	Template::add_message('submit-confirm','confirm', 'Submission received, it will be judged shortly.');
	return $subm;
}
