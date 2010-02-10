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
			LogEntry::delete_for_entity($_REQUEST['clear_log_entity']);
			$this->add_message('log','confirm','Log messages deleted');
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
		echo "<tr><th>Time</th><th>Entity</th><th>Message</th></tr>";
		foreach($entries as $entry) {
			echo "<tr>";
			echo "<td>" . format_date_compact($entry->time);
			echo "<td>" . ($entry->entity_path ? $entry->entity_path : "-");
			echo "<td>" . nl2br(htmlspecialchars($entry->message));
			echo "</tr>";
		}
		echo "</table>";
	}
	
}

$view = new View();
$view->write();
