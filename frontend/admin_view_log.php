<?php

require_once('../lib/bootstrap.inc');
require_once('../lib/DateRange.php');

// -----------------------------------------------------------------------------
// View the error log
// -----------------------------------------------------------------------------

class View extends Template {
	function __construct() {
		Authentication::require_admin();
		$this->is_admin_page = true;
	}
	
	function title() {
		return "Error log";
	}
	
	function write_body() {
		echo('<p>Only messages stored in the database are shown here. The type of messages that are stored in the database depends on the configuration.</p>');
		$this->write_entries(Log::fetch_last(100));
	}
	function write_entries($entries) {
		echo "<table>";
		echo "<tr><th>Time</th><th>Type</th><th>Entity</th><th>Message</th><th>Judge host</th><th>User</th><th>IP</th></tr>";
		foreach($entries as $entry) {
			echo "<tr>";
			echo "<td>" . format_date_compact($entry->time);
			echo "<td>" . htmlspecialchars(LogLevel::toString($entry->level));
			echo "<td>" . ($entry->entity_path ? "<a href=\"index.php" . htmlspecialchars($entry->entity_path) . "\">" . $entry->entity_path . "</a>" : "-");
			echo "<td>" . nl2br(htmlspecialchars($entry->message));
			echo "<td>" . ($entry->judge_host ? htmlspecialchars($entry->judge_host) : '-');
			echo "<td>" . ($entry->userid ? htmlspecialchars(User::by_id($entry->userid, false)->name_and_login()) : '-');
			echo "<td>" . ($entry->ip ? htmlspecialchars($entry->ip) : '-');
			echo "</tr>";
		}
		echo "</table>";
	}
	
}

$view = new View();
$view->write();
