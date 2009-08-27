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

function is_windows() {
	return strpos(php_uname('s'),'indows') !== false;
}

class Judgement {
	private $entity;
	private $subm;
	
	
	
	function __construct($subm) {
	}
}

// Run a shell command in a safe way
function safe_system($cmd,$args,$limits=array()) {
	// TODO make this actually safe!!!
	// build command line
	// windows fix
	if (is_windows()) {
		$cmd = str_replace('/',"\\",$cmd);
	}
	$command = escapeshellcmd($cmd);
	foreach($args as $arg) {
		$command .= ' ' . escapeshellarg($arg);
	}
	if (!file_exists($cmd)) {
		throw new Exception("Command not found: $cmd");
	}
	// execute
	system($command, $retval);
	return $retval == 0;
}
function set_submission_status($subm, $status) {
	$subm->set_status($status);
	//die("one is enough");
}
function compile_submission($infile, $outfile, $errfile, $language, $entity) {
	// compiler to use
	$compiler = "compilers/" . $language['name'] . '.sh';
	return safe_system($compiler, array($infile, $outfile, $errfile));
}
function prepare_reference_output($subm, $entity) {
	// TODO
}

function run_submission($program, $infile, $outfile, $errfile, $entity) {
	return safe_system('compilers/run.sh', array($program, $infile, $outfile, $errfile));
}
function run_checker($out1, $out2, $diff, $entity) {
	return safe_system('compilers/diff.sh', array($out1, $out2, $diff));
}

function judge_submission($subm) {
	$entity = $subm->entity();
	
	// do we need to compile at all?
	if (!$entity->attribute_bool('compile')) {
		return Status::PASSED_DEFAULT;
	}
	
	// what type of file do we have?
	$language = Util::language_info( $entity->attribute('language') );
	if ($language['name'] == 'any') {
		$language = Util::language_from_filename($subm->file_name);
	}
	if ($language['name'] == 'unknown') {
		// unknown language -> failure
		return Status::FAILED_LANGUAGE;
	}
	
	// move to temp directory
	//$tempdir = Util::create_new_directory(TEMP_JUDGING_DIR,'judge');
	$tempdir = $subm->file_path . '/out';
	@mkdir($tempdir);
	chmod($tempdir,0755);
	$source_file = $tempdir .'/'. $subm->file_name;
	copy($subm->file_path . '/code/' . $subm->file_name, $source_file);
	
	// 1. extract archive
	$is_archive = false;
	if (isset($language['archive_extract'])) {
		if ($entity->attribute_bool('allow archives')) {
			// TODO: Check this during submit
			return Status::FAILED_LANGUAGE;
		}
		throw new Exception("TODO: archives");
		safe_system($language['archive_extract'], $source_file);
		// look for the actual source file
	}
	
	// 1. compile
	$exe_file = $source_file.'.exe';
	if (!compile_submission($source_file, $exe_file, $tempdir .'/compiler.err', $language, $entity)) {
		return Status::FAILED_COMPILE;
	}
	
	// 2. prepare testset
	$testset = new Testset($entity);
	
	// 3. run testset
	foreach($testset->test_cases() as $case) {
		echo "  case: " . $case, "\n";
		$case_input       = COURSE_DIR . $subm->entity_path . $case . '.in';
		$case_ref_output  = COURSE_DIR . $subm->entity_path . $case . '.out';
		$case_my_output   = $tempdir .'/'. $case . '.out';
		$case_my_error    = $tempdir .'/'. $case . '.err';
		$case_diff_output = $tempdir .'/'. $case . '.diff';
		// run program
		if (!run_submission($exe_file, $case_input, $case_my_output, $case_my_error, $entity)) {
			return Status::FAILED_RUN;
		}
		if (!file_exists($case_my_output)) {
			file_put_contents($case_my_error, "No output file created");
			return Status::FAILED_RUN;
		}
		// compare input/output
		if (!run_checker($case_my_output, $case_ref_output, $case_diff_output, $entity)) {
			return Status::FAILED_COMPARE;
		}
		
	}
	return Status::PASSED_COMPARE;
}

function judge_submission_and_update($subm) {
	$status = judge_submission($subm);
	set_submission_status($subm, $status);
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
		judge_submission_and_update($subm);
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

