

<?php

require_once('bootstrap.inc');

Authentication::require_user();

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

$entity = Entity::get(@$_SERVER['PATH_INFO']);

function handle_uploaded_submission($entity, $file) {
	global $error;
	if (!$entity->attribute('submitable')) {
		$error = "No submissions can be made here.";
		return false;
	}
	if (!$entity->active()) {
		$error = "The deadline has passed for this assignment.";
		return false;
	}
	if ($file['error'] != UPLOAD_ERR_OK) {
		$error = file_upload_error_message($file['error']);
		return false;
	}
	// match filename with regex
	$file_regex = $entity->attribute('filename regex','');
	if ($file_regex != '') {
		if (!preg_match("/$file_regex/", $file['name'])) {
			$error = "Uploaded file does not match specified filename pattern";
			return false;
		}
	}
	// move file into pending folder
	$temp_name = tempnam(PENDING_DIR, 'submission');
	move_uploaded_file($file['tmp_name'], $temp_name);
	$error = 'upload successful';
	// add to database
	$subm = Submission::make_new($entity, $temp_name);
	// assign users
	$subm->add_user(Authentication::current_user());
	return $subm;
}

if ($entity->attribute('submitable')) {
	if (isset($_FILES['file'])) {
		$file = $_FILES['file'];
		handle_uploaded_submission($entity, $file);
	}
}


class Page extends Template {
	function title() { return "A simple submission form"; }
	function write_body() {
		global $error;
		if (isset($error)) {
			echo "<div class=\"error\">$error</div>";
		}
		$suffix = @$_SERVER['PATH_INFO'];
	
?><form action="submit.php<?php echo $suffix; ?>" method="post" enctype="multipart/form-data">
  <input type="file" name="file" id="file" style="color:green">
  <input type="submit" name="submit" value="Submit" id="submit">
</form>
<script type="text/javascript">
<!--
  var file_control = document.getElementById('file');
  file_control.onchange = function() {
	var ok = file_control.value.match(/\.java$/);
	document.getElementById('submit').style.backgroundColor = ok ? 'white' : 'red';
  }
//-->
</script>
<?php
}}
new Page();
