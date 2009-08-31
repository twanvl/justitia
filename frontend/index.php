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

class Page extends PageWithEntity {
	function __construct() {
		// find active entity
		parent::__construct();
		try {
			// submit?
			$uploaded = handle_uploaded_submission($this->entity);
			if ($uploaded || $this->entity->count_pending_submissions() > 0) {
				$this->auto_refresh_to = 'index.php' . $this->entity->path();
				$this->auto_refresh    = 1;
			}
		} catch (Exception $e) {
			ErrorPage::die_fancy($e->getMessage());
		}
	}
	
	function write_body() {
		if ($this->entity->submitable()) {
			$this->write_submitable_page();
		} else {
			$this->write_overview_page();
		}
	}
	
	// ---------------------------------------------------------------------
	// Submitable entity page
	// ---------------------------------------------------------------------
	
	function write_usergroup_view() {
		$group = UserGroup::current();
		$max_size = $this->entity->attribute('max group size');
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
			echo '<li><a href="user_group_add.php?redirect='.urlencode($current_page).'">[add]</a></li>';
		}
		echo '</ul>';
	}
	
	function write_submit_form() {
		$this->write_messages('submit');
		
		$this->write_form_begin('index.php'.$this->entity->path(), 'post', true);
		$this->write_form_table_begin();
		echo "<tr><td>Students</td><td>";
		$this->write_usergroup_view();
		echo "</td></tr>";
		$this->write_form_table_field('file', 'file', 'Select file');
		$this->write_form_table_end();
		$this->write_form_end('Submit');
	}
	
	function write_submitable_entity_info() {
		echo "<table>";
		echo "<tr><td>Can submit</td><td>" . format_bool($this->entity->active()) . "</td>";
		$active_range = $this->entity->active_range();
		if ($active_range->start > now()) {
			echo "<tr><td>Starts</td><td>" . format_date($active_range->start) . "</td>";
		}
		echo "<tr><td>Deadline</td><td>"   . format_date($active_range->end, true) . "</td>";
		echo "<tr><td>Language</td><td>"   . ($this->entity->attribute('language')) . "</td>";
		//echo "<tr><td>Archives allowed</td><td>" . format_bool($this->entity->attribute_bool('allow archives')) . "</td>";
		echo "<tr><td>Judging</td><td>";
		if ($this->entity->compile()) {
			if ($this->entity->has_testcases()) {
				echo "Your submission will be compiled and tested. ";
			} else {
				echo "Your submission will be compiled. ";
			}
			if ($this->entity->attribute_bool('keep best')) {
				echo "The best solution counts.";
			} else {
				echo "The last solution counts.";
			}
		} else {
			echo "All submissions are accepted.";
		}
		echo "</td>";
		echo "</table>";
	}
	
	function write_submitable_page() {
		$submissions = Authentication::current_user()->submissions_to($this->entity);
		
		$this->write_block_begin('Problem description');
		$this->write_submitable_entity_info();
		$this->write_block_end();
		
		// submission form
		$last_submission = Authentication::current_user()->last_submission_to($this->entity);
		if ($this->entity->active()) {
			$passed = Status::to_status($last_submission) >= Status::PENDING;
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
				$is_interesting = $this->entity->is_more_interesting_submission($subm,$last_submission);
				if ($is_interesting) $last_submission = $subm;
				// write
				$this->write_block_begin(
					'Submission '. $i,
					'collapsable block submission '
					 . ($is_interesting ? '' : 'collapsed ')
					 . Status::to_css_class($subm)
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

$page = new Page();
$page->write();
