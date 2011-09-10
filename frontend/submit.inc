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
		Log::warning('New submission failed: No file was uploaded', $entity->path());
		return false;
	}
	
	// is submission allowed?
	if (!$entity->submitable()) {
		Template::add_message('submit','error', "No submissions can be made here, this is not an assignment.");
		Log::warning('New submission failed: No submissions can be made here, this is not an assignment.', $entity->path());
		return false;
	}
	if (!$entity->active()) {
		Template::add_message('submit','error', "The deadline has passed for this assignment.");
		Log::warning('New submission failed: The deadline has passed for this assignment.', $entity->path());
		return false;
	}
	
	// is the group ok?
	$group = UserGroup::current();
	$max_size = $entity->max_group_size();
	if (count($group) > $max_size) {
		Template::add_message('submit','error', "There are too many students in this group, please remove someone.");
		Log::warning('New submission failed: There are too many students in this group, please remove someone.', $entity->path());
		return false;
	}
	
	// are the uploads okay?
	$filenames = array();
	foreach ($files as $file) {
		if ($file['error'] != UPLOAD_ERR_OK) {
			Template::add_message('submit','error', file_upload_error_message($file['error']));
			Log::warning("Upload of new submission failed: " . file_upload_error_message($file['error']), $entity->path());
			return false;
		}
		// is this an archive?
		if (substr($file['name'],-4) == '.zip') {
			// zip archive, try to open it to check if it is valid
			$zip = zip_open($file['tmp_name']);
			if (!is_resource($zip)) {
				Template::add_message('submit','error', "Not a valid zip file (\"" . $file['tmp_name'] . "\")");
				Log::warning('New submission failed: Not a valid zip file ("' . $file['tmp_name'] . '")', $entity->path());
				return false;
			}
			// get all filenames, so we can check if they match the supplied regex
			while ($entry = zip_read($zip)) {
				$entry_name = zip_entry_name($entry);
				$entry_name = str_replace('\\','/',$entry_name);
				if (substr($entry_name,1) == '/' || strpos($entry_name,'./') !== false) {
					// this is an invalid filename
					Template::add_message('submit','error', "Invalid filename in zip archive (\"$entry_name\")");
					Log::warning("New submission failed: Invalid filename in zip archive (\"$entry_name\")" . file_upload_error_message($file['error']), $entity->path());
					return false;
				} else if (substr($entry_name,-1) == '/') {
					// this is a directory name, don't count it
				} else {
					$filenames[] = $entry_name;
				}
			}
			zip_close($zip);
		} else {
			$filenames[] = $file['name'];
		}
	}
	
	
	// are multiple files allowed?
	if (count($filenames) > 1 && !$entity->allow_multiple_files()) {
		Template::add_message('submit','error', "Only single-file submissions are allowed.");
		Log::warning("New submission failed: Only single-file submissions are allowed.", $entity->path());
		return false;
	}
	
	// match filename with regex
	$file_regex = $entity->filename_regex();
	if ($file_regex != '') {
		foreach ($filenames as $filename) {
			if (!preg_match("/^($file_regex)$/",$filename)) {
				Template::add_message('submit','error',	"Uploaded file does not match specified filename pattern, \"$filename\" does not match pattern \"$file_regex\"");
				Log::warning("New submission failed: Uploaded file does not match specified filename pattern, \"$filename\" does not match pattern \"$file_regex\"", $entity->path());
				return false;
			}
		}
	}
	
	return true;
}

function put_zipfile_contents($subm, $zip_filename) {
	$zip = zip_open($zip_filename);
	while ($entry = zip_read($zip)) {
		// sanatize filename
		$entry_name = zip_entry_name($entry);
		$entry_name = str_replace('\\','/',$entry_name);
		$entry_name = str_replace('./','',$entry_name); // don't want things like ../
		while ($entry_name[0] == '/') $entry_name = substr($entry_name,1);
		
		// is this a directory?
		if (substr($entry_name,-1) == '/') continue;
		
		// read contents
		$entry_body = '';
		if (!zip_entry_open($zip,$entry)) {
			// error
			zip_close($zip);
			Template::add_message('submit','error',	"Can't read zip file");
			Log::warning("New submission failed: Can't read zip file");
			return false;
		}
		while (true) {
			$part = zip_entry_read($entry);
			if ($part === false || $part === '') break;
			$entry_body .= $part;
		}
		zip_entry_close($entry);
		
		// store
		$subm->put_file($subm->code_filename($entry_name), $entry_body);
	}
	zip_close($zip);
	return true;
}

function put_normal_file_contents($subm, $temp_filename, $filename) {
	$filename = str_replace('/','',$filename);
	$subm->put_file($subm->code_filename($filename), file_get_contents($temp_filename));
}

function perform_upload($entity, $files) {
	// add to database
	$subm = Submission::make_new($entity);
	
	// add files
	foreach ($files as $file) {
		if (substr($file['name'],-4) == '.zip') {
			if (!put_zipfile_contents($subm, $file['tmp_name'])) return false;
		} else {
			put_normal_file_contents($subm, $file['tmp_name'], $file['name']);
		}
		unlink($file['tmp_name']);
	}
	
	// assign users
	$group = UserGroup::current();
	foreach ($group as $user) {
		$subm->add_user($user);
	}
	
	// finalize
	$subm->set_status(Status::PENDING);
	
	// Log
	Log::info("Uploaded submission #" . $subm->submissionid, $entity->path());
	
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
		$ok = perform_upload($entity, $files);
	}
	if (!$ok) {
		foreach ($files as $file) @unlink($file['tmp_name']);
	}
	return $ok;
}
