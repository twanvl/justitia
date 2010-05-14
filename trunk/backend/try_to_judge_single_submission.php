<?php

require_once('../lib/bootstrap.inc');
require_once('../lib/DateRange.php');

// -----------------------------------------------------------------------------
// Judge a single submission
// -----------------------------------------------------------------------------

if (!isset($_SERVER['argv'])) {
	die("This program must be started from the console.");
}
if (count($_SERVER['argv']) < 2) {
	die("Usage: try_to_judge_submission.php <SUBMISSIONID>.\n\n" .
	    "For debug purposes, try to judge a submission and don't discard the temporary files.");
} else {
	$submissionid = $_SERVER['argv'][1];
}

try {
	// Retrieve the submission
	$subm = Submission::by_id($submissionid);

	// Some information on this submission
	echo "\n";
	echo "Submission id: ", $subm->submissionid, "\n";
	echo "Submission to: ", $subm->entity_path, "\n";
	echo "Submited on:   ", format_date($subm->time), "\n";
	echo "Submission by: ", User::names_text($subm->users()), "\n"; // this slows things down
	echo "\n";

	// Let's judge it
	try {
		$judgement = new SubmissionJudgement($subm);
		$judgement->judge(false);
	} catch (Exception $e) {
		echo "Error during judging!\n", $e;
	}
	
	echo "\n";
	echo "Source files:  ", implode("\n               ",$judgement->get_source_file_names()), "\n";
	echo "Exe file:      ", $judgement->get_exe_file_name(), "\n";
	echo "Tempdir:       ", $judgement->get_and_keep_tempdir(), "\n";
	echo "\n";
	echo "Done. The tempdir contains all files created and used during judging.";
	
	// clean up
	$judgement->__destruct();
	
} catch (Exception $e) {
	echo $e->getMessage(), "\n\n";
	echo "Stack trace:\n";
	echo $e->getTraceAsString();
}