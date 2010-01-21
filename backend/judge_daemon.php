<?php

require_once('../lib/bootstrap.inc');
require_once('../lib/DateRange.php');

// -----------------------------------------------------------------------------
// Configuration
// -----------------------------------------------------------------------------

if (!isset($_SERVER['argv'])) {
	die("The judgedaemon must be started from the console.");
}

define('VERBOSE', true);

// for shell scripts
putenv("JUSTITIA_BACKEND_DIR=" . getcwd());

// Identity of this host (database object)
$self = JudgeDaemon::add();

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
	echo "|       Judge daemon   on ".sprintf('%-20s',$self->name)."                                |\n";
	echo "|                                                                             |\n";
	echo "+=============================================================================+\n";
}

// -----------------------------------------------------------------------------
// Main loop
// -----------------------------------------------------------------------------

function micro_nonce() {
	$t = microtime(true);
	return "[" . substr(strval($t - intval($t)), 2,5) . "]";
}

function judge_a_single_submission() {
	// Clear all caches, otherwise we would use old data!
	Entity::clear_cache();
	// Retrieve a submission
	global $my_name;
	$subm = Submission::get_pending_submission($my_name . micro_nonce());
	if (!$subm) {
		// no submissions right now
		sleep(DAEMON_SLEEP_TIME);
		return;
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
		$judgement = new SubmissionJudgement($subm);
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
	if (VERBOSE) {
		echo "Memory usage: ",memory_get_usage(),"\n";
	}
}

while (true) {
	// we are active
	$self->ping();
	
	// are there requests to change our status?
	$prev_status = $self->status;
	$self->update_status();
	if ($self->status != $prev_status) {
		echo "Switching to status: " . $self->status_text() . "\n";
	}
	if ($self->status == JudgeDaemon::MUST_STOP || $self->status == JudgeDaemon::MUST_RESTART || $self->status == JudgeDaemon::STOPPED) {
		break;
	} else if ($self->status == JudgeDaemon::PAUSED) {
		// Don't judge submissions for a while
		sleep(5);
		continue;
	} else if ($self->status == JudgeDaemon::ACTIVE) {
		// judge
		judge_a_single_submission();
		// don't be too fast
		usleep(100);
	} else {
		// something is wrong, we'd better die
		echo "Unknown status: $self->status\n";
		break;
	}
}

// Done
$must_stop = $self->status == JudgeDaemon::MUST_STOP;
$self->set_status(JudgeDaemon::MUST_STOP);

// Magic exit code, so the looping script can determine what to do
if ($must_stop) exit(100);
