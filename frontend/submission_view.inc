<?php

require_once('../lib/bootstrap.inc');

// -----------------------------------------------------------------------------
// Main 'entity' page
// -----------------------------------------------------------------------------

// link to download a file
function download_link($subm,$file,$text, $cond,$before,$after) {
	if ($cond || Authentication::is_admin()) {
		return $before
		     . "<a href=\"download.php/$subm->submissionid/$file\">$text"
		     . ($cond ? '' : ' <small>(hidden)</small>')
		     . "</a>"
		     . $after;
	}
}

// ---------------------------------------------------------------------
// Submission information
// ---------------------------------------------------------------------

function write_submission($subm, $entity = null, $include_entity = false) {
	if ($entity == null) {
		$entity = $subm->entity();
	}
	
	$type = Status::base_status($subm->status);
	echo "<table>";
	if ($include_entity) {
		echo "<tr><td>Problem</td><td><a href=\"index.php".htmlspecialchars($entity->path())."\">" . htmlspecialchars($entity->title()) . "</a>";
		echo " <small>(" . htmlspecialchars($entity->path()) . ")</small>";
		echo "</td></tr>\n";
	}
	echo "<tr><td>Submitted on</td><td>" . format_date($subm->time) . "</td></tr>";
	echo "<tr><td>Submitted by</td><td>" . User::names_html($subm->users()) . "</td></tr>";
	echo '<tr><td>Files</td><td><a href="download.php/'.$subm->submissionid.'/code/'.urlencode($subm->filename)
		                        .'">Download submitted files</a></td></tr>';
	echo "<tr><td>Status</td><td>" . Status::to_text($subm);
	if ($type == Status::FAILED_COMPILE) {
		echo download_link($subm,'out/compiler.err','view error message',  $entity->show_compile_errors(),  ' (',')');
	}
	echo "</td></tr>";
	if (Authentication::is_admin()) {
		if ($subm->status() != Status::PENDING) {
			echo "<tr><td>Judged by</td><td>" . htmlspecialchars($subm->judge_host);
			echo "</td></tr>";
		}
		echo "<tr><td>Actions</td><td>";
		if ($subm->status() != Status::PENDING) {
			echo "<a href=\"admin_view_submission.php?rejudge=1&amp;submissionid=$subm->submissionid\">rejudge</a> | ";
		}
		echo "<a href=\"admin_view_submission.php?delete=1&amp;submissionid=$subm->submissionid\">delete</a>";
		echo "</td></tr>";
	}
	echo "</table>";
	//if ($type == Status::FAILED_COMPARE || $type == Status::FAILED_RUN) // only write testset if failed?
	write_testset_details($subm, $entity);
}

// ---------------------------------------------------------------------
// Failure details
// ---------------------------------------------------------------------

function write_testset_details($subm, $entity) {
	$cases = $subm->get_file('testcases');
	if (!$cases) return;
	$cases = unserialize($cases);
	// testcase output
	echo "<table class=\"testcase-details\">";
	foreach ($cases as $case => $status) {
		// status, this is a bit of a hack, we should look at exit codes
		$class = Status::to_css_class($status);
		$case_status = Status::to_testcase_text($status);
		
		// description/hint
		$desc = '';
		if ($class == 'failed') {
			$desc_file = $subm->input_filename("$case.desc");
			if (file_exists($desc_file)) {
				$desc = "Hint: " . file_get_contents($desc_file);
			} else {
				// TODO: description from attributes?
			}
		}
		
		// input/output/error downloads
		$downloads = '';
		if ($class != 'skipped') {
			if ($subm->input_exists("$case.in")) {
				$downloads .= download_link($subm,"in/$case.in", 'input',  $entity->show_input_output_for($case), '',' | ');
			}
			if ($case_status == 'Runtime error') {
				// don't show output at all for runtime errors
			} else if ($class == 'failed') {
				if ($subm->input_exists("$case.out")) {
					$downloads .= download_link($subm,"in/$case.out",'expected output',  $entity->show_input_output_for($case), '',' | ');
				}
				if ($subm->output_exists("$case.out")) {
					$downloads .= download_link($subm,"out/$case.out",'your output',  $entity->show_input_output_for($case), '',' | ');
				}
				if ($subm->output_exists("$case.diff")) {
					$downloads .= download_link($subm,"out/$case.diff",'difference',  $entity->show_input_output_for($case), '',' | ');
				}
			} else {
				if ($subm->input_exists("$case.out")) {
					$downloads .= download_link($subm,"in/$case.out",'output',  $entity->show_input_output_for($case), '',' | ');
				}
			}
		}
		if ($case_status == 'Runtime error') {
			if ($subm->output_exists("$case.err")) {
				$downloads .= download_link($subm,"out/$case.err",'error message',  $entity->show_runtime_errors_for($case), '',' | ');
			}
		}
		$downloads = substr($downloads,0,-3);
		
		// write it
		$rows  = 1 + (strlen($desc) > 0 ? 1 : 0) + (strlen($downloads) > 0 ? 1 : 0);
		echo "<tr class=\"$class\"><td rowspan=\"$rows\">Test case " . htmlspecialchars($case) . "</td><td><span>$case_status</span></td></tr>";
		if (strlen($desc) > 0)      echo "<tr><td>$desc</td></tr>";
		if (strlen($downloads) > 0) echo "<tr><td>$downloads</td></tr>";
	}
	echo "</table>";
}
