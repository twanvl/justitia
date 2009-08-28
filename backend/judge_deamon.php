<?php

require_once('../lib/bootstrap.inc');
require_once('../lib/DateRange.php');

// -----------------------------------------------------------------------------
// Configuration
// -----------------------------------------------------------------------------

if (!isset($_SERVER['argv'])) {
	die("The judgedeamon must be started from the console.");
}

define('SLEEP_TIME', 5);
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
	echo "|      _  _                    _     _     _                                  |\n";
 	echo "|     | \| |  ___  __ __ __   /_\   | |_  | |_    ___   _ _    __ _           |\n";
 	echo "|     | .` | / -_) \ V  V /  / _ \  |  _| | ' \  / -_) | ' \  / _` |          |\n";
 	echo "|     |_|\_| \___|  \_/\_/  /_/ \_\  \__| |_||_| \___| |_||_| \__,_|          |\n";
	echo "|                                                                             |\n";
	echo "|       Judge deamon   on ".sprintf('%-20s',$my_name)."                                |\n";
	echo "|                                                                             |\n";
	echo "+=============================================================================+\n";
}

// -----------------------------------------------------------------------------
// Making judgements
// -----------------------------------------------------------------------------

class Judgement {
	private $entity;
	private $subm;
	private $tempdir;
	// language of submission
	private $language;
	// temporary files
	private $source_file;
	private $exe_file;
	
	function __construct($subm) {
		$this->subm = $subm;
		$this->entity = $subm->entity();
	}
	
	function __destruct() {
		if (isset($this->tempdir)) {
			$this->tempdir->__destruct();
			unset($this->tempdir);
		}
	}
	
	// Judge the submission, and update the database
	function judge() {
		$status = $this->do_judge();
		$this->subm->set_status($status);
		$this->__destruct();
	}
	
	// Judge the submission, return status
	private function do_judge() {
		// do we need to compile at all?
		if (!$this->entity->attribute_bool('compile')) {
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
		
		// prepare testset
		$testset = new Testset($this->entity);
		// TODO
		
		// run testset
		$test_results = array();
		$status = Status::PASSED_COMPARE;
		
		foreach($testset->test_cases() as $case) {
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
	
	private function determine_language() {
		// what type of file do we have?
		// determine from specification
		$this->language = Util::language_info( $this->entity->attribute('language') );
		// determine from extension
		if ($this->language['name'] == 'any') {
			$this->language = Util::language_from_filename($this->subm->filename);
		}
		// unknown language -> failure
		return $this->language['name'] != 'unknown';
	}
	
	private function download_source() {
		$this->source_file = $this->tempdir->file($this->subm->filename);
		$contents = $this->subm->get_file($this->subm->code_filename());
		if ($contents === false) return false;
		file_put_contents($this->source_file, $contents);
		return true;
	}
	
	private function create_tempdir() {
		// create temporary directory
		$this->tempdir = new Tempdir('','judge');
		if (!file_exists($this->tempdir->dir)) return false;
		chmod($this->tempdir->dir,0755);
		return true;
	}
	
	private function extract_archive() {
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
	
	private function compile() {
		// compiler script to use
		$compiler = $this->entity->attribute('compiler');
		if ($compiler == '') $compiler = $this->language['name'];
		$compiler = "compilers/$compiler.sh";
		// compile
		$this->exe_file = $this->source_file . '.exe';
		$compile_err_file = $this->tempdir->file('compiler.err');
		$result =SystemUtil::safe_command($compiler, array($this->source_file, $this->exe_file, $compile_err_file));
		if (!$result) {
			$this->put_tempfile('compiler.err');
		}
		return $result;
	}
	
	private function run_case($case) {
		// copy case input
		$case_input  = $this->tempdir->file("$case.in");
		$case_output = $this->tempdir->file("$case.out");
		$case_error  = $this->tempdir->file("$case.err");
		copy($this->entity->data_path() . "$case.in", $case_input);
		// runner
		$runner = $this->entity->attribute('runner');
		$runner = "runners/$runner.sh";
		// run program, store results
		$result = SystemUtil::safe_command($runner, array($this->exe_file, $case_input, $case_output, $case_error));
		if (!file_exists($case_output)) {
			file_put_contents($case_output, "No output file created");
			$result = false;
		}
		$this->put_tempfile("$case.out");
		$this->put_tempfile("$case.err");
		return $result;
	}
	
	private function check_case($case) {
		$case_ref  = $this->entity->data_path() . "$case.out";
		$case_my   = $this->tempdir->file("$case.out");
		$case_diff = $this->tempdir->file("$case.diff");
		// run checker
		$checker = $this->entity->attribute('checker');
		$checker = "checkers/$checker.sh";
		$result = SystemUtil::run_command($checker, array($case_my, $case_ref, $case_diff));
		$this->put_tempfile("$case.diff");
		return $result;
	}
	
	private function put_tempfile($file) {
		$this->subm->put_file(
			$this->subm->output_filename($file),
			file_get_contents($this->tempdir->file($file))
		);
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
		sleep(SLEEP_TIME);
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
	// don't be too fast
	usleep(100);
}

