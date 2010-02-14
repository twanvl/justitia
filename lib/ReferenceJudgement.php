<?php

// -----------------------------------------------------------------------------
// Compiling and running the reference implementation
//
// This class is used from the backend, not from the webserver.
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
		echo "\nNote: Testcase reference output does not exist or is out of date, generating it now.\n";
		$this->create_output_dir();
		if (!$this->find_sourcefile()) {
			echo "No reference implementation found.\n";
			return false;
		}
		if (($status = $this->prepare_and_compile()) != 0) {
			echo "Compiling reference implementation failed with status " . Status::to_text($status) . "\n";
			if (file_exists($this->output_dir . '/compiler.err')) {
				echo file_get_contents($this->output_dir . '/compiler.err');
			} else {
				echo "<no message>\n";
			}
			echo "\n";
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
	
	function create_output_dir() {
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
	// Warn when we truncate the output file
	protected function truncate_file($file, $contents, $max_file_size, $actual_file_size) {
		/*
		// should we really warn about this?
		$actual_file_size_up = intval( ($actual_file_size + 1000*10-1) / (1000*10) ) * (1000*10); // round up to get a nicer number
		LogEntry::log("The reference implementation produced a file that is too large\n$file has size $actual_file_size, while max is $max_file_size\nTo suppress this warning, add  'filesize limit: $actual_file_size_up' to the into file", $this->entity);
		*/
		return $contents; // don't actually truncate
	}
	protected function should_truncate_files() {
		return false;
	}
	
};
