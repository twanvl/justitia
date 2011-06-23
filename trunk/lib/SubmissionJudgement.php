<?php

// -----------------------------------------------------------------------------
// Making judgements for user submissions
//
// This class is used from the backend, not from the webserver.
//
// For each submission an instance of SubmissionJudgement is created.
// Calling $this->judge() will judge the submission, and update its status in the database
// -----------------------------------------------------------------------------

class SubmissionJudgement extends JudgementBase {
	private $subm;
	
	function __construct($subm) {
		$this->subm = $subm;
		parent::__construct($subm->entity());
	}
	
	// Judge the submission, and update the database
	function judge($cleanup = true) {
		$status = $this->do_judge();	
		if(Status::is_passed($status) AND $this->subm->entity()->deadline_passed($this->subm->time)) {
			$status = Status::MISSED_DEADLINE;
		}
		$this->subm->set_status($status);
		if ($cleanup) $this->__destruct();
	}
	
	// Judge the submission, return status
	private function do_judge() {
		$status = $this->prepare_and_compile();
		if ($status != 0) return $status;
		$status = $this->prepare_testset();
		if ($status != 0) return $status;
		$status = $this->run_testcases();
		return $status;
	}
	
	private function prepare_testset() {
		// prepare testset
		if (!$this->entity->testcase_reference_output_exists()) {
			// generate testcase output
			$ref_output_generator = new ReferenceJudgement($this->entity);
			try {
				$ref_output_generator->build_testset_outputs();
			} catch (Exception $e) {
				$msg = "Failed to build reference implementation.\n" . $e->getMessage();
				LogEntry::log($msg, $this->entity);
				echo $msg . "\n";
				return Status::FAILED_INTERNAL;
			}
			$ref_output_generator->__destruct();
		}
	}
	
	private function run_testcases() {
		// run testset
		$test_results = array();
		$status = Status::PASSED_COMPARE;
		
		foreach($this->entity->testcases() as $case) {
			echo "  case: " . $case . "\n";
			if ($status != Status::PASSED_COMPARE) {
				$test_results[$case] = Status::NOT_DONE;
			} else if (!$this->run_case($case)) {
				$status = Status::FAILED_RUN;
				$test_results[$case] = Status::FAILED_RUN;
			} else if (!$this->check_case($case)) {
				$status = Status::FAILED_COMPARE;
				$test_results[$case] = Status::FAILED_COMPARE;
			} else {
				$test_results[$case] = Status::PASSED_COMPARE;
			}
		}
		
		// write status to file
		$this->subm->put_file('testcases',serialize($test_results));
		
		return $status;
	}
	
	// interface for JudgementBase
	
	protected function get_source_files() {
		$names = $this->subm->get_code_filenames();
		$files = array();
		foreach($names as $code_name => $name) {
			$contents = $this->subm->get_file($code_name);
			$files[$name] = $contents;
		}
		return $files;
	}
	
	protected function put_output_file_contents($file, $contents) {
		$this->subm->put_file($this->subm->output_filename($file),$contents);
	}
	
}
