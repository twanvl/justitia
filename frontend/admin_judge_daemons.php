<?php

require_once('../lib/bootstrap.inc');
require_once('../lib/DateRange.php');

// -----------------------------------------------------------------------------
// The active judge daemons
// -----------------------------------------------------------------------------

class View extends Template {
	function __construct() {
		Authentication::require_admin();
		$this->is_admin_page = true;
		// updates?
		$this->update_judge_status();
	}
	
	function update_judge_status() {
		if (!isset($_REQUEST['judgeid'],$_REQUEST['status'])) return;
		$status = $_REQUEST['status'];
		if ($_REQUEST['judgeid'] == 'all') {
			JudgeDaemon::set_status_all($status);
			$this->add_message('judge','confirm','Status of all judges set to ' . JudgeDaemon::status_to_text($status));
		} else {
			$judge = JudgeDaemon::by_id($_REQUEST['judgeid']);
			if (!$judge) return; // it already died
			if ($status == JudgeDaemon::MUST_STOP && $judge->is_inactive()) {
				$status = JudgeDaemon::STOPPED; // stop right now
			}
			$judge->set_status($status);
			$this->add_message('judge','confirm','Status set to ' . $judge->status_text());
		}
	}
	
	function title() {
		return "Judge daemons";
	}
	
	function write_body() {
		// TODO: filters?
		$this->write_messages('judge');
		JudgeDaemon::cleanup();
		$this->write_judges(JudgeDaemon::all());
	}
	
	function write_judges($judges) {
		if (empty($judges)) {
			echo "<em>There are no active judges!</em>";
			return;
		}
		
		echo "<table>";
		echo "<tr><th>Name</th><th>Status</th><th>Started</th><th>Last heared from</th></tr>";
		foreach ($judges as $judge) {
			echo "<tr>";
			echo "<td>" . htmlspecialchars($judge->name);
			echo "<td>" . $judge->status_text();
			echo "<td>" . format_date_compact($judge->start_time);
			echo "<td>" . (time() - $judge->ping_time) . " second(s) ago";
			echo "<td>";
			$links = array();
			if ($judge->status == JudgeDaemon::PAUSED) {
				$links['unpause'] = "admin_judge_daemons.php?judgeid=$judge->judgeid&amp;status=" . JudgeDaemon::ACTIVE;
			} else {
				$links['pause'] = "admin_judge_daemons.php?judgeid=$judge->judgeid&amp;status=" . JudgeDaemon::PAUSED;
			}
			$links['stop']    = "admin_judge_daemons.php?judgeid=$judge->judgeid&amp;status=" . JudgeDaemon::MUST_STOP;
			$links['restart'] = "admin_judge_daemons.php?judgeid=$judge->judgeid&amp;status=" . JudgeDaemon::MUST_RESTART;
			$this->write_links($links);
			echo "</tr>";
		}
		echo "</table>";
		
		// Control all
		$links = array();
		$links['unpause all'] = "admin_judge_daemons.php?judgeid=all&amp;status=" . JudgeDaemon::ACTIVE;
		$links['pause all']   = "admin_judge_daemons.php?judgeid=all&amp;status=" . JudgeDaemon::PAUSED;
		$links['stop all']    = "admin_judge_daemons.php?judgeid=all&amp;status=" . JudgeDaemon::MUST_STOP;
		$links['restart all'] = "admin_judge_daemons.php?judgeid=all&amp;status=" . JudgeDaemon::MUST_RESTART;
		$this->write_links($links);
	}
	
}

$view = new View();
$view->write();
