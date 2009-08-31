<?php

class Testset {
	private $entity;
	private $testcases;
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
		$this->testcases = array();
		foreach (new DirectoryIterator($entity->data_path()) as $child) {
			$filename = $child->getFilename();
			if ($filename{0} == '.') continue;
			if (substr($filename,-3) == '.in') {
				$this->testcases []= substr($filename,0,-3);
			}
			// for convenience, pick the first program source file as the reference impl, if none is set
			if ($this->reference_impl === NULL) {
				if (Util::is_code($filename)) {
					$this->reference_impl = $filename;
				}
			}
		}
		sort($this->testcases);
		// are there any tests?
		if (empty($this->testcases) && $this->reference_impl !== NULL) {
			Log::error("Reference implementation, but no tests cases for ".$entity->path());
		} else if (!empty($this->testcases) && $this->reference_impl === NULL) {
			Log::error("Tests cases, but no reference implementation for ".$entity->path());
		}
	}
	
	// Get a list of test base files (i.e. without the ".in" extension)
	function testcases() {
		return $this->testcases;
	}
	
	// Are there no test cases?
	function is_empty() {
		return empty($this->testcases);
	}
	
	// Is the generated output up to date?
	// Or alternatively, are the output files not generated at all
	function output_up_to_date() {
		if ($this->is_empty()) return true; // no tests -> always up to date
		if ($this->reference_impl !== NULL) {
		}
		foreach($this->testcases as $f) {
			$in_name = $this->entity->data_path() . $f . '.in';
			$in_name = $this->entity->data_path() . '.generated' . $f . '.in';
		}
		return true;
	}
}
