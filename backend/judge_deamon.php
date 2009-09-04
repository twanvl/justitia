<?php

require_once('../lib/bootstrap.inc');
require_once('../lib/DateRange.php');

// -----------------------------------------------------------------------------
// Configuration
// -----------------------------------------------------------------------------

if (!isset($_SERVER['argv'])) {
	die("The judgedeamon must be started from the console.");
}

define('VERBOSE', true);

// Name of this host.
// Add randomness, so two judges can run on one computer if needed
$salt = '';
for ($i = 0 ; $i < 5 ; ++$i) {
	$salt .= chr(mt_rand(65,65+25));
}
$my_name = trim(`hostname`) . '[' . $salt . ']';

// -----------------------------------------------------------------------------
// Welcome message
// -----------------------------------------------------------------------------

if (VERBOSE) {
	echo "+=============================================================================+\n";
	echo "|                  __                   __     _    __     _                  |\n";
	echo "|                 / /  __  __   _____  / /_   (_)  / /_   (_)  ____ _         |\n";
	echo "|            __  / /  / / / /  / ___/ / __/  / /  / __/  / /  / __ `/         |\n";
	echo "|           / /_/ /  / /_/ /  (__  ) / /_   / /  / /_   / /  / /_/ /          |\n";
	echo "|           \____/   \__,_/  /____/  \__/  /_/   \__/  /_/   \__,_/           |\n";
	echo "|                                                                             |\n";
	echo "|       Judge deamon   on ".sprintf('%-20s',$my_name)."                                |\n";
	echo "|                                                                             |\n";
	echo "+=============================================================================+\n";
}

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
			SystemUtil::run_command($this->language['archive_extract'], $this->source_file);
			// look for the actual source file
		}
		return true;
	}
	
	// Compile $source_file to $exe_file
	protected function compile() {
		// compiler script to use
		$compiler = $this->entity->compiler();
		if ($compiler == '') $compiler = $this->language['name'];
		$compiler = "compilers/$compiler.sh";
		// compile
		$this->exe_file = $this->source_file . '.exe';
		$compile_err_file = $this->tempdir->file('compiler.err');
		make_file_readable($this->source_file);
		make_file_writable($this->exe_file);
		make_file_writable($compile_err_file);
		$limits = $this->entity->compile_limits();
		$result =SystemUtil::safe_command($compiler, array($this->source_file, $this->exe_file, $compile_err_file), $limits);
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
		$runner = "runners/$runner.sh";
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
		$result = SystemUtil::safe_command($runner, array($this->exe_file, $case_input, $case_output, $case_error), $limits, $case_limit_error);
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
		$checker = "checkers/$checker.sh";
		// the files
		$case_ref  = $this->entity->testcase_reference_output($case);
		$case_my   = $this->tempdir->file("$case.out");
		$case_diff = $this->tempdir->file("$case.diff");
		make_file_writable($case_diff);
		if (!file_exists($case_ref)) {
			throw new Exception("Reference implementation does not exists:\n$case_ref");
		}
		// run checker
		$result = SystemUtil::run_command($checker, array($case_my, $case_ref, $case_diff));
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
	}
	// Store a file from the tempdir
	protected function put_tempfile($file) {
		$contents = file_get_contents($this->tempdir->file($file));
		$this->put_output_file_contents_checked($file, $contents);
	}
	
}

// -----------------------------------------------------------------------------
// Compiling reference implementation
// -----------------------------------------------------------------------------

class GenerateReferenceOutput extends JudgementBase {
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

// -----------------------------------------------------------------------------
// Making judgements
// -----------------------------------------------------------------------------

class Judgement extends JudgementBase {
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
			$ref_output_generator = new GenerateReferenceOutput($this->entity);
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


// -----------------------------------------------------------------------------
// Main loop
// -----------------------------------------------------------------------------

function micro_nonce() {
	$t = microtime(true);
	return "[" . substr(strval($t - intval($t)), 2,4) . "]";
}

while (true) {
	// Clear all caches, otherwise we would use old data!
	Entity::clear_cache();
	// Retrieve a submission
	$subm = Submission::get_pending_submission($my_name . micro_nonce());
	if (!$subm) {
		// no submissions right now
		sleep(DAEMON_SLEEP_TIME);
		continue;
	}
	// Some information on this submission
	if (VERBOSE) {
		echo "\n";
		echo "Submission id: ", $subm->submissionid, "\n";
		echo "Submission to: ", $subm->entity_path, "\n";
		echo "Submited on:   ", format_date($subm->time), "\n";
		echo "Submission by: ", User::names_text($subm->users()), "\n"; // this slows things down
	}
	// Let's judge it
	try {
		$judgement = new Judgement($subm);
		$judgement->judge();
	} catch (Exception $e) {
		// TODO: shout louder
		echo "Error during judging!\n", $e;
	}
	if (VERBOSE) {
		echo "Result:        ", Status::to_text($subm), "\n";
	}
	// free up some memory
	unset($judgement);
	unset($subm);
	if (function_exists('gc_collect_cycles')) {
		// this doesn't exist in PHP < 5.3.0 :(
		gc_collect_cycles();
	}
	echo "Memory usage: ",memory_get_usage(),"\n";
	// don't be too fast
	usleep(100);
}

