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

function check_upload($entity, &$files) {
	// are there any files at all?
	foreach ($files as $i => $file) {
		// when uploading multiple files we add a blank control at the end
		// the upload for that control is not valid
		if ($file['error'] == UPLOAD_ERR_NO_FILE && empty($file['name']) && $file['size'] == 0) {
			unset($files[$i]);
		}
	}
	if (count($files) < 1) {
		Template::add_message('submit','error', 'No file was uploaded');
		return false;
	}
	
	// is submission allowed?
	if (!$entity->submitable()) {
		Template::add_message('submit','error', "No submissions can be made here.");
		return false;
	}
	if (!$entity->active()) {
		Template::add_message('submit','error', "The deadline has passed for this assignment.");
		return false;
	}
	
	// are multiple files allowed?
	if (count($files) > 1 && !$entity->allow_multiple_files()) {
		Template::add_message('submit','error', "Only single-file submissions are allowed.");
		return false;
	}
	
	// is the group ok?
	$group = UserGroup::current();
	$max_size = $entity->max_group_size();
	if (count($group) > $max_size) {
		Template::add_message('submit','error', "There are too many students in this group, please remove someone.");
		return false;
	}
	
	// are the uploads okay?
	$file_regex = $entity->filename_regex();
	foreach ($files as $file) {
		if ($file['error'] != UPLOAD_ERR_OK) {
			Template::add_message('submit','error', file_upload_error_message($file['error']));
			return false;
		}
	}
	
	// match filename with regex
	if ($file_regex != '') {
		foreach ($files as $file) {
			if (!preg_match("/$file_regex/", $file['name'])) {
				Template::add_message('submit','error', "Uploaded file does not match specified filename pattern");
				return false;
			}
		}
	}
	
	return true;
}

function perform_upload($entity, $files) {
	// add to database
	$subm = Submission::make_new($entity);
	
	// add files
	foreach ($files as $file) {
		$filename = str_replace('/','',$file['name']);
		$subm->put_file($subm->code_filename($filename), file_get_contents($file['tmp_name']));
		unlink($file['tmp_name']);
	}
	
	// assign users
	$group = UserGroup::current();
	foreach ($group as $user) {
		$subm->add_user($user);
	}
	
	// finalize
	$subm->set_status(Status::PENDING);
	
	// success
	Template::add_message('submit-confirm','confirm', 'Submission received, it will be judged shortly.');
	return $subm;
}

function unshuffle($upload) {
	// the PHP $_FILES has a stupid orginization:
	//   array('name' => array('file1',file2'), 'tmp_name' => array('tmp1','tmp2'))
	// instead of
	//   array(array('name'=>'file1','tmp_name'=>'tmp1'), array('name'=>'file2','tmp_name'=>'tmp2'))
	$out = array();
	foreach ($upload as $key => $values) {
		foreach ($values as $i => $value) {
			$out[$i][$key] = $value;
		}
	}
	return $out;
}

function handle_uploaded_submission($entity) {
	if (!isset($_FILES['files'])) return false;
	$files = unshuffle($_FILES['files']);
	$ok = check_upload($entity, $files);
	if ($ok) {
		perform_upload($entity, $files);
	} else {
		foreach ($files as $file) @unlink($file['tmp_name']);
		return false;
	}
	return false;
}
