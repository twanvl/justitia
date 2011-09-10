<?php

// -----------------------------------------------------------------------------
// Compiling and running the reference implementation
//
// This class is used from the backend, not from the webserver.
// -----------------------------------------------------------------------------

class ReferenceJudgement extends JudgementBase {
	private $output_dir;
	
	function __construct($entity) {
		parent::__construct($entity);
	}
	
	// Build the testset outputs based on a reference implementation
	// store outputs in <entitydir>/.output
	function build_testset_outputs() {
		echo "\nNote: Testcase reference output does not exist or is out of date, generating it now.\n";
		$this->create_output_dir();
		if (($status = $this->prepare_and_compile()) != 0) {
			$msg = "Compiling failed with status " . Status::to_text($status) . "\n";
			if (file_exists($this->output_dir . '/compiler.err')) {
				$msg .= file_get_contents($this->output_dir . '/compiler.err', 0,null,0, 10000);
			} else {
				$msg .= "<no message>\n";
			}
			file_put_contents($this->output_dir . "/buildresult", "Failed to build reference implementation.");
			Log::warning("Failed to build reference implementation.", $this->entity->path());
			throw new Exception($msg);
		}
		// Now build all testcases
		foreach($this->entity->testcases() as $case) {
			echo "  case: " . $case . "\n";
			if (!$this->run_case($case)) {
				$msg = "Runtime error for case $case.\n";
				if (file_exists($this->output_dir . "/$case.err")) {
					$msg .= file_get_contents($this->output_dir . "/$case.err", 0,null,0, 10000);
				}
				file_put_contents($this->output_dir . "/buildresult", "Failed to run reference implementation.");
				Log::warning("Failed to run reference implementation.", $this->entity->path());
				throw new Exception($msg);
			}
		}
		// Done
		echo "Reference output generated successfully.\n\n";
		file_put_contents($this->output_dir . "/buildresult", "Reference output generated successfully.");
		return true;
	}
	
	function create_output_dir() {
		// make output dir
		$this->output_dir = $this->entity->data_path() . ".generated";
		@mkdir($this->output_dir);
	}
	
	// interface for JudgementBase
	
	protected function get_source_files() {
		$filenames = explode_whitespace($this->entity->reference_implementation());
		$files = array();
		foreach($filenames as $name) {
			$full_path = $this->entity->data_path() . $name;
			if (!file_exists($full_path)) {
				throw new Exception("Reference implementation not found: $name");
			}
			$contents = file_get_contents($full_path);
			$files[$name] = $contents;
		}
		return $files;
	}
	
	protected function put_output_file_contents($file, $contents) {
		file_put_contents($this->output_dir . '/' . $file, $contents);
	}
	
	// Warn when we truncate the output file
	protected function truncate_file($file, $contents, $max_file_size, $actual_file_size) {
		// should we really warn about this?
		$actual_file_size_up = intval( ($actual_file_size + 1000*10-1) / (1000*10) ) * (1000*10); // round up to get a nicer number
		Log::warning("The reference implementation produced a file that is too large\n$file has size $actual_file_size, while max is $max_file_size\nTo suppress this warning, add  'filesize limit: $actual_file_size_up' to the into file", $this->entity->path());
		return $contents; // don't actually truncate
	}
	protected function should_truncate_files() {
		return false;
	}
	
};
