<?php

require_once('JudgementBase.php');

// -----------------------------------------------------------------------------
// Compiling and running the reference implementation
// -----------------------------------------------------------------------------

class ReferenceJudgement extends JudgementBase {
	private $sourcefile_path;
	private $output_dir;
	
	function __construct($entity) {
		parent::__construct($entity);
	}
	
	// Build the testset outputs based on a reference implementation
	// store outputs in <entitydir>/.output
	function build_testset_outputs() {
		echo "\nNote: Testcase reference output does not exist, generating it now.\n";
		$this->crate_output_dir();
		if (!$this->find_sourcefile()) {
			echo "No reference implementation found.\n";
			return false;
		}
		if (($status = $this->prepare_and_compile()) != 0) {
			echo "Compiling reference implementation failed with status " . Status::to_text($status) . "\n";
			return false;
		}
		// Now build all testcases
		foreach($this->entity->testcases() as $case) {
			echo "  case: " . $case . "\n";
			if (!$this->run_case($case)) {
				echo "Runtime error for case $case.\n";
				return false;
			}
		}
		// Done
		echo "Reference output generated successfully.\n\n";
		return true;
	}
	
	function crate_output_dir() {
		// make output dir
		$this->output_dir = $this->entity->data_path() . ".generated";
		@mkdir($this->output_dir);
	}
	
	function find_sourcefile() {
		// find source file
		$this->sourcefile_path = $this->entity->data_path()
		                       . $this->entity->reference_implementation();
		return file_exists($this->sourcefile_path);
	}
	
	// interface for JudgementBase
	
	protected function get_source_filename() {
		return pathinfo($this->sourcefile_path,PATHINFO_BASENAME);
	}
	
	protected function get_source_file_contents() {
		return file_get_contents($this->sourcefile_path);
	}
	
	protected function put_output_file_contents($file, $contents) {
		file_put_contents($this->output_dir . '/' . $file, $contents);
	}
	
};
