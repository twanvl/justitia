<?php

require_once('JudgementBase.php');
require_once('ReferenceJudgement.php');

// -----------------------------------------------------------------------------
// Making judgements for user submissions
// -----------------------------------------------------------------------------

class SubmissionJudgement extends JudgementBase {
	private $subm;
	
	function __construct($subm) {
		$this->subm = $subm;
		parent::__construct($subm->entity());
	}
	
	// Judge the submission, and update the database
	function judge() {
		$status = $this->do_judge();
		$this->subm->set_status($status);
		$this->__destruct();
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
			if (!$ref_output_generator->build_testset_outputs()) {
				throw new Exception("Failed to build reference implementation.");
				return Status::FAILED_INTERNAL;
			}
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
	
	protected function get_source_filename() {
		return $this->subm->filename;
	}
	
	protected function get_source_file_contents() {
		return $this->subm->get_file($this->subm->code_filename());
	}
	
	protected function put_output_file_contents($file, $contents) {
		$this->subm->put_file($this->subm->output_filename($file),$contents);
	}
	
}
