<?php

require_once('../lib/DateRange.php');

// -----------------------------------------------------------------------------
// Judgement base : compiling & running submissions and reference implementations
// -----------------------------------------------------------------------------

function make_file_readable($file) {
	chmod($file,0644);
}
function make_file_executable($file) {
	chmod($file,0755);
}
function make_file_writable($file) {
	touch($file);
	chmod($file,0666);
}

abstract class JudgementBase {
	protected $entity;
	// language of the submission
	private $language;
	// directory for temp files
	private $tempdir;
	// temporary files
	private $source_file;
	private $exe_file;
	
	protected abstract function get_source_filename();
	protected abstract function get_source_file_contents();
	protected abstract function put_output_file_contents($file,$contents);
	
	
	function __construct($entity) {
		$this->entity = $entity;
	}
	
	function __destruct() {
		if (isset($this->tempdir)) {
			$this->tempdir->__destruct();
			unset($this->tempdir);
		}
	}
	
	// Prepare the submission for judging: compile it
	// returns 0 if success
	protected function prepare_and_compile() {
		// do we need to compile at all?
		if (!$this->entity->compile()) {
			return Status::PASSED_DEFAULT;
		}
		
		if (!$this->determine_language()) {
			return Status::FAILED_LANGUAGE;
		}
		if (!$this->create_tempdir()) {
			throw new Exception("Failed to create tempdir");
			return Status::FAILED_INTERNAL;
		}
		if (!$this->download_source()) {
			throw new Exception("Failed to find submission source");
			return Status::FAILED_INTERNAL;
		}
		if (!$this->extract_archive()) {
			return Status::FAILED_LANGUAGE;
		}
		if (!$this->compile()) {
			return Status::FAILED_COMPILE;
		}
		return 0;
	}
	
	// What language is the source code in? store in $this->language
	protected function determine_language() {
		// what type of file do we have?
		// determine from specification
		$this->language = Util::language_info( $this->entity->attribute('language') );
		// determine from extension
		if ($this->language['name'] == 'any') {
			$this->language = Util::language_from_filename($this->get_source_filename());
		}
		// unknown language -> failure
		return $this->language['name'] != 'unknown';
	}
	
	protected function create_tempdir() {
		// create temporary directory
		$this->tempdir = new Tempdir('','judge');
		if (!file_exists($this->tempdir->dir)) return false;
		chmod($this->tempdir->dir,0755);
		return true;
	}
	
	// Store source in tempdir
	protected function download_source() {
		$this->source_file = $this->tempdir->file($this->get_source_filename());
		$contents = $this->get_source_file_contents();
		if ($contents === false) return false;
		file_put_contents($this->source_file, $contents);
		return true;
	}
	
	protected function extract_archive() {
		// extract archive?
		$is_archive = false;
		if (isset($this->language['archive_extract'])) {
			if ($this->entity->attribute_bool('allow archives')) {
				// TODO: Check this during submit
				return false;
			}
			throw new Exception("TODO: archives");
			SystemUtil::run_command(false, $this->language['archive_extract'], $this->source_file);
			// look for the actual source file
		}
		return true;
	}
	
	// Compile $source_file to $exe_file
	protected function compile() {
		// compiler script to use
		$compiler = $this->entity->compiler();
		if ($compiler == '') $compiler = $this->language['name'];
		$compiler = getcwd() . "/compilers/$compiler.sh";
		// flags?
		$flags = $this->entity->compiler_flags();
		// copy some files?
		$files_to_copy = $this->entity->compiler_files();
		foreach ($files_to_copy as $filename) {
			$local_name = $this->tempdir->file($filename);
			copy($this->entity->data_path() . $filename, $local_name);
			echo "$local_name\n";
			make_file_readable($local_name);
		}
		// compile
		$this->exe_file = $this->source_file . '.exe';
		$compile_err_file = $this->tempdir->file('compiler.err');
		make_file_readable($this->source_file);
		make_file_writable($this->exe_file);
		make_file_writable($compile_err_file);
		$limits = $this->entity->compile_limits();
		$result = SystemUtil::safe_command($this->tempdir->dir, $compiler, array($this->source_file, $this->exe_file, $compile_err_file, $flags), $limits);
		if (!$result) {
			$this->put_tempfile('compiler.err');
		} else {
			make_file_executable($this->exe_file);
		}
		return $result;
	}
	
	// Run with input from $case
	protected function run_case($case) {
		// runner
		$runner = $this->entity->runner();
		$runner = getcwd() . "/runners/$runner.sh";
		// copy case input, prepare output files
		$case_input  = $this->tempdir->file("$case.in");
		$case_output = $this->tempdir->file("$case.out");
		$case_error  = $this->tempdir->file("$case.err");
		$case_limit_error = $this->tempdir->file("$case.limit-err");
		copy($this->entity->testcase_input($case), $case_input);
		make_file_readable($case_input);
		make_file_writable($case_output);
		make_file_writable($case_error);
		make_file_writable($case_limit_error);
		// run program
		$limits = $this->entity->run_limits();
		$result = SystemUtil::safe_command($this->tempdir->dir, $runner, array($this->exe_file, $case_input, $case_output, $case_error), $limits, $case_limit_error);
		if (!file_exists($case_output)) {
			file_put_contents($case_output, "<<NO OUTPUT FILE CREATED>>");
			$result = false;
			echo "     No output file created\n";
		}
		if (!$result && file_exists($case_limit_error) && filesize($case_limit_error) > 0) {
			// use limit error message as error output
			copy($case_limit_error, $case_error);
		}
		// store results
		$this->put_tempfile("$case.out");
		$this->put_tempfile("$case.err");
		return $result;
	}
	
	// Compare the output of a testcase agains the reference output
	protected function check_case($case) {
		// checker
		$checker = $this->entity->checker();
		$checker = getcwd() . "/checkers/$checker.sh";
		// the files
		$case_ref  = $this->entity->testcase_reference_output($case);
		$case_my   = $this->tempdir->file("$case.out");
		$case_diff = $this->tempdir->file("$case.diff");
		make_file_writable($case_diff);
		if (!file_exists($case_ref)) {
			throw new Exception("Reference implementation does not exists:\n$case_ref");
		}
		// run checker
		$result = SystemUtil::run_command($this->tempdir->dir, $checker, array($case_my, $case_ref, $case_diff));
		if (!$result) {
			$this->put_tempfile("$case.diff");
			echo "     Output does not match\n";
		}
		return $result;
	}
	
	// Store a file, but check output size first
	protected function put_output_file_contents_checked($file, $contents) {
		$max_file_size = intval($this->entity->filesize_limit());
		$content_size  = strlen($contents);
		if ($content_size > $max_file_size) {
			// don't allow files to be too large
			echo "Putting file of size: ",$content_size,"  while max = ",$max_file_size,"\n";
			$contents = substr($contents,0,$max_file_size) . "\n<<FILE TOO LARGE>>";
		}
		$this->put_output_file_contents($file,$contents);
		// try to clean up
		unset($file);
		unset($contents);
	}
	
	// Store a file from the tempdir
	protected function put_tempfile($file) {
		$max_file_size = intval($this->entity->filesize_limit()) + 1;
		$contents = file_get_contents($this->tempdir->file($file), 0,null,0, $max_file_size);
		$this->put_output_file_contents_checked($file, $contents);
		// try to clean up
		unset($file);
		unset($contents);
	}
	
}
