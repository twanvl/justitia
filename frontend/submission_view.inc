<?php

require_once('../lib/bootstrap.inc');

// -----------------------------------------------------------------------------
// Main 'entity' page
// -----------------------------------------------------------------------------

// link to download a file
function download_link($subm,$file,$text) {
	return "<a href=\"download.php/$subm->submissionid/$file\">$text</a>";
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
		if ($entity->show_compile_errors() || Authentication::is_admin()) {
			echo ' ('.download_link($subm,'out/compiler.err','view error message').')';
		}
	}
	echo "</td></tr>";
	if (Authentication::is_admin()) {
		if ($subm->status() != Status::PENDING) {
			echo "<tr><td>Judged by</td><td>" . htmlspecialchars($subm->judge_host);
			echo " (<a href=\"admin_rejudge_submission.php?rejudge=$subm->submissionid\">rejudge</a>)";
			echo "</td></tr>";
		}
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
		if ($class != 'skipped' && ($entity->show_input_output_for($case) || Authentication::is_admin())) {
			if ($subm->input_exists("$case.in")) {
				$downloads .= download_link($subm,"in/$case.in", 'input') . ' | ';
			}
			if ($class == 'failed') {
				if ($subm->input_exists("$case.out")) {
					$downloads .= download_link($subm,"in/$case.out",'expected output') . ' | ';
				}
				if ($subm->output_exists("$case.out")) {
					$downloads .= download_link($subm,"out/$case.out",'your output') . ' | ';
				}
				if ($subm->output_exists("$case.diff")) {
					$downloads .= download_link($subm,"out/$case.diff",'difference') . ' | ';
				}
			} else {
				if ($subm->input_exists("$case.out")) {
					$downloads .= download_link($subm,"in/$case.out",'output') . ' | ';
				}
			}
		}
		if ($case_status == 'Runtime error' && ($entity->show_runtime_errors_for($case) || Authentication::is_admin())) {
			if ($subm->output_exists("$case.err")) {
				$downloads .= download_link($subm,"out/$case.err",'error message') . ' | ';
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
