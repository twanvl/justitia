<?php

class Testset {
	private $entity;
	private $test_cases;
	private $reference_impl;
	
	function __construct($entity) {
		$this->entity = $entity;
		// name of reference implementation file
		$this->reference_impl = $entity->attribute("reference implementation");
		if (!file_exists($entity->data_path() . $this->reference_impl)) {
			Log::error("Reference implementation not found for ".$entity->path());
			$this->reference_impl = NULL;
		}
		// initialize
		$this->test_cases = array();
		foreach (new DirectoryIterator($entity->data_path()) as $child) {
			$filename = $child->getFilename();
			if ($filename{0} == '.') continue;
			if (substr($filename,-3) == '.in') {
				$this->test_cases []= substr($filename,0,-3);
			}
			// for convenience, pick the first program source file as the reference impl, if none is set
			if ($this->reference_impl === NULL) {
				if (Util::is_code($filename)) {
					$this->reference_impl = $filename;
				}
			}
		}
		sort($this->test_cases);
		// are there any tests?
		if (empty($this->test_cases) && $this->reference_impl !== NULL) {
			Log::error("Reference implementation, but no tests cases for ".$entity->path());
		} else if (!empty($this->test_cases) && $this->reference_impl === NULL) {
			Log::error("Tests cases, but no reference implementation for ".$entity->path());
		}
	}
	
	// Get a list of test base files (i.e. without the ".in" extension)
	function test_cases() {
		return $this->test_cases;
	}
	
	// Are there no test cases?
	function is_empty() {
		return empty($this->test_cases);
	}
	
	// Is the generated output up to date?
	// Or alternatively, are the output files not generated at all
	function output_up_to_date() {
		if ($this->is_empty()) return true; // no tests -> always up to date
		if ($this->reference_impl !== NULL) {
		}
		foreach($this->test_cases as $f) {
			$in_name = $this->entity->data_path() . $f . '.in';
			$in_name = $this->entity->data_path() . '.generated' . $f . '.in';
		}
		return true;
	}
}
