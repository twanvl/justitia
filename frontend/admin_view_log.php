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
		// delete stuff
		$this->delete_log_entries();
	}
	
	function delete_log_entries() {
		if (isset($_REQUEST['clear_log_entity'])) {
			LogEntry::delete_by_entity($_REQUEST['clear_log_entity']);
			$this->add_message('log','confirm','Log messages deleted');
		}
		if (isset($_REQUEST['delete_logid'])) {
			LogEntry::delete_by_id($_REQUEST['delete_logid']);
			$this->add_message('log','confirm','Log message deleted');
		}
		if (isset($_REQUEST['redirect'])) {
			Util::redirect($_REQUEST['redirect']);
		}
	}
	
	function title() {
		return "Error log";
	}
	
	function write_body() {
		$this->write_messages('log');
		$this->write_entries(LogEntry::all());
	}
	function write_entries($entries) {
		echo "<table>";
		echo "<tr><th>Time</th><th>Entity</th><th>Message</th><th> </th></tr>";
		foreach($entries as $entry) {
			echo "<tr>";
			echo "<td>" . format_date_compact($entry->time);
			echo "<td>" . ($entry->entity_path ? "<a href=\"index.php" . htmlspecialchars($entry->entity_path) . "\">" . $entry->entity_path . "</a>" : "-");
			echo "<td>" . nl2br(htmlspecialchars($entry->message));
			echo "<td>" . "<a href=\"admin_view_log.php?delete_logid=$entry->logid\">[delete]</a>";
			echo "</tr>";
		}
		echo "</table>";
	}
	
}

$view = new View();
$view->write();
