<?php

require_once('../lib/bootstrap.inc');
require_once('./submit.inc');
require_once('./submission_view.inc');

// -----------------------------------------------------------------------------
// Main 'entity' page
// -----------------------------------------------------------------------------

function format_bool($b) {
	return $b ? "yes" : "no";
}

class View extends PageWithEntity {
	function __construct() {
		// find active entity
		parent::__construct();
		// submit?
		$uploaded = handle_uploaded_submission($this->entity);
		if ($uploaded) {
			$self_url = 'index.php' . $this->entity->path() . '?made_submission=' . $uploaded->submissionid;
			Util::redirect($self_url);
		}
	}
	
	function write_body() {
		if (Authentication::is_admin()) {
			$this->write_error_log();
		}
		if ($this->entity->submitable()) {
			$this->write_submitable_page();
		} else {
			$this->write_overview_page();
		}
	}
	
	// ---------------------------------------------------------------------
	// Errors
	// ---------------------------------------------------------------------
	
	function write_error_log() {
		$errors = LogEntry::all_for_entity($this->entity);
		if (!$errors) return;
		
		$this->write_block_begin('Errors in the problem configuration','block failed');
		
		echo "<ul>";
		foreach($errors as $e) {
			echo "<li>" . nl2br(htmlspecialchars($e->message)) . "</li>";
		}
		echo "</ul>";
		
		// link to clear the log
		$current_page = 'index.php' . $this->entity->path();
		echo "<a href=\"admin_view_log.php?redirect=".urlencode($current_page)."&amp;clear_log_entity=".urlencode($this->entity->path())."\">clear messages</a>";
		
		$this->write_block_end();
	}
	
	// ---------------------------------------------------------------------
	// Submitable entity page
	// ---------------------------------------------------------------------
	
	function write_usergroup_view() {
		$group = UserGroup::current();
		$max_size = $this->entity->max_group_size();
		echo '<ul class="user-group">';
		$current_page = 'index.php' . $this->entity->path();
		foreach ($group as $user) {
			echo '<li>'.htmlspecialchars($user->name_and_login());
			if ($user != Authentication::current_user()) {
				echo ' <a href="user_group_remove.php?remove='.$user->userid.'&amp;redirect='.urlencode($current_page).'">[remove]</a>';
			}
			echo '</li>';
		}
		if (count($group) < $max_size) {
			echo '<li class="add"><a href="user_group_add.php?redirect='.urlencode($current_page).'">[add another student]</a></li>';
		}
		echo '</ul>';
		if (count($group) > $max_size) {
			$size_msg = $max_size == 1 ? "a single student" : "$max_size students";
			echo "<div class=\"user-group-error\">Only groups of $size_msg are allowed, remove someone from the list.</div>";
		}
	}
	
	function write_submit_form() {
		$this->write_messages('submit');
		
		$this->write_form_begin('index.php'.$this->entity->path(), 'post', true);
		$this->write_form_table_begin();
		echo "<tr><td>Students</td><td>";
		$this->write_usergroup_view();
		echo "</td></tr>";
		if ($this->entity->allow_multiple_files()) {
			$this->write_form_table_field('file multiple', 'files[]', 'Select files');
		} else {
			$this->write_form_table_field('file', 'files[]', 'Select file');
		}
		$this->write_form_table_end();
		$this->write_form_end('Submit');
	}
	
	function write_submitable_entity_info() {
		echo "<table>";
		if ($this->entity->description()) {
			echo "<tr><td>Description</td><td>" . nl2br(htmlspecialchars($this->entity->description())) . "</td>";
		}
		echo "<tr><td>Can submit</td><td>" . format_bool($this->entity->active()) . "</td>";
		$active_range = $this->entity->active_range();
		if ($active_range->start > now()) {
			echo "<tr><td>Starts</td><td>" . format_date($active_range->start) . "</td>";
		}
		echo "<tr><td>Deadline</td><td>"   . format_date($active_range->end, true) . "</td>";
		echo "<tr><td>Language</td><td>"   . $this->entity->language()->name . "</td>";
		echo "<tr><td>Judging</td><td>";
		if ($this->entity->compile()) {
			if ($this->entity->has_testcases()) {
				echo "Your submission will be compiled and tested. ";
			} else {
				echo "Your submission will be compiled. ";
			}
			if ($this->entity->attribute_bool('keep best')) {
				// Arnold says: "This is confusing, don't show"
				//echo "The best solution counts.";
			} else {
				echo "The last solution counts.";
			}
		} else {
			echo "All submissions are accepted.";
		}
		// downloadable files?
		$files = $this->entity->downloadable_files();
		if ($files) {
			$downloads = array();
			foreach ($files as $file) {
				$ext = pathinfo($file, PATHINFO_EXTENSION);
				$downloads[] = '<a href="download_entity.php' . htmlspecialchars($this->entity->path()) . '?f=' . urlencode($file) . '" class="file '.$ext.'">'
				             . htmlspecialchars($file)
				             . '</a>';
			}
			if (count($files) > 1) {
				array_unshift($downloads, '<a href="download_entity.php' . htmlspecialchars($this->entity->path()) . '?all=1" class="file zip">All files as zip</a>');
			}
			echo "<tr><td>Files</td><td class=\"list-like\">" . implode(' | ', $downloads) . "</td></tr>";
		}
		echo "</td>";
		echo "</table>";
	}
	
	function write_submitable_page() {
		$submissions = Authentication::current_user()->submissions_to($this->entity);
		$made_submission = isset($_REQUEST['made_submission']) ? $_REQUEST['made_submission'] : false;
		
		$this->write_block_begin('Problem description');
		$this->write_submitable_entity_info();
		$this->write_block_end();
		
		// submission form
		$last_submission = Authentication::current_user()->last_submission_to($this->entity);
		if ($this->entity->active()) {
			$passed = Status::to_status($last_submission) >= Status::PENDING // file passed
			       && !isset($_FILES['file']); // no new submission
			$this->write_block_begin('Submit', 'collapsable block' . ($passed ? ' collapsed' : ''));
			$this->write_submit_form();
			$this->write_block_end();
		}
		
		echo "<h2>Submissions</h2>";
		$this->write_messages('submit-confirm');
		
		// submissions that were made
		if (empty($submissions)) {
			echo "<em>no submissions have been made for this assignment.</em>";
		} else {
			$i = count($submissions);
			foreach($submissions as $subm) {
				// is this an interesting submission?
				$made_this_submission = $made_submission !== false && $subm->submissionid == $made_submission;
				$is_interesting = $this->entity->is_more_interesting_submission($subm,$last_submission)
				               || $made_this_submission;
				if ($is_interesting) $last_submission = $subm;
				// write
				$this->write_block_begin(
					'Submission '. $i,
					'collapsable block submission '
					 . ($is_interesting ? '' : 'collapsed ')
					 . ($made_this_submission ? 'appear ' : '')
					 . Status::to_css_class($subm)
					 , ''
					 , "submission-$subm->submissionid"
				);
				write_submission($subm,$this->entity);
				$this->write_block_end();
				$i--;
			}
		}
		
	}
	
	// ---------------------------------------------------------------------
	// Non-Submitable page
	// ---------------------------------------------------------------------
	
	function write_overview_item($e) {
		if (!$e->visible()) return;
		$class = '';
		if ($e->submitable()) {
			$subm = Authentication::current_user()->status_of_last_submission_to($e);
			$class .= Status::to_css_class($subm) . ' ';
		}
		if (!$e->active()) {
			$class .= 'inactive ';
		}
		$this->write_block_begin($e->title(), 'collapsed linky block '.$class, 'index.php' . $e->path());
		$this->write_block_end();
	}
	function write_overview_page() {
		foreach ($this->entity->children() as $e) {
			$this->write_overview_item($e);
		}
	}
	
}

$view = new View();
$view->write();
