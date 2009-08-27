<?php

require_once('../lib/bootstrap.inc');
require_once('./submit.inc');

// -----------------------------------------------------------------------------
// Main 'entity' page
// -----------------------------------------------------------------------------

function format_bool($b) {
	return $b ? "yes" : "no";
}

function download_link($subm,$file,$text) {
	return "<a href=\"download.php/$subm->submissionid/$file\">$text</a>";
}

class Page extends Template {
	private $entity;
	
	function __construct() {
		// find active entity
		try {
			$user = Authentication::require_user();
			$this->entity = Entity::get(@$_SERVER['PATH_INFO'], !$user->is_admin);
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
	
	function title() {
		return $this->entity->title();
	}
	
	function write_body() {
		if ($this->entity->attribute_bool('submitable')) {
			$this->write_submitable_page();
		} else {
			$this->write_overview_page();
		}
	}
	
	// ---------------------------------------------------------------------
	// Navigation
	// ---------------------------------------------------------------------
	
	function get_nav_children($e) {
		$result = array();
		foreach ($e->children() as $e) {
			if (!$e->visible()) continue;
			
			$class = '';
			if ($e->is_ancestor_of($this->entity)) {
				$class .= 'ancestor ';
			}
			if ($e->attribute_bool('submitable')) {
				$subm = Authentication::current_user()->status_of_last_submission_to($e);
				$class .= Status::to_css_class($subm) . ' ';
			}
			if (!$e->active()) {
				$class .= 'inactive ';
			}
			
			$result []= array(
				'title' => $e->title(),
				'url'   => 'index.php' . $e->path(),
				'class' => $class
			);
		}
		return $result;
	}
	function get_nav() {
		$result = parent::get_nav();
		foreach ($this->entity->ancestors() as $e) {
			$result []= $this->get_nav_children($e);
		}
		return $result;
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
		echo "<tr><td>Deadline</td><td>"   . format_date($this->entity->active_range()->end) . "</td>";
		echo "<tr><td>Language</td><td>"   . ($this->entity->attribute('language')) . "</td>";
		echo "<tr><td>Archives allowed</td><td>" . format_bool($this->entity->attribute_bool('allow archives')) . "</td>";
		echo "</table>";
	}

	function write_submission($subm) {
		$type = Status::base_status($subm->status);
		echo "<table>";
		echo "<tr><td>Submitted on</td><td>" . format_date($subm->time) . "</td>";
		echo "<tr><td>Submitted by</td><td>" . User::names_html($subm->users()) . "</td>";
		echo '<tr><td>Files</td><td><a href="download.php/'.$subm->submissionid.'/code/'.urlencode($subm->file_name)
		                           .'">Download submitted files</a></td>';
		echo "<tr><td>Status</td><td>" . Status::to_text($subm);
		if ($type == Status::FAILED_COMPILE) {
			if ($this->entity->show_compile_errors()) {
				echo ' ('.download_link($subm,'out/compiler.err','view error message').')';
			}
		}
		echo "</td>";
		echo "</table>";
		if ($type == Status::FAILED_COMPARE || $type == Status::FAILED_RUN) {
			echo "<hr>";
			$this->write_testset_details($subm);
		}
	}
	
	function write_submitable_page() {
		$submissions = Authentication::current_user()->submissions_to($this->entity);
		
		$this->write_block_begin('Problem description');
		$this->write_submitable_entity_info();
		$this->write_block_end();
		
		// submission form
		if ($this->entity->active()) {
			$last_submission = Authentication::current_user()->last_submission_to($this->entity);
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
				$this->write_block_begin(
					'Submission '. $i,
					'collapsable block submission '
					 . ($subm->submissionid == $last_submission->submissionid ? '' : 'collapsed ')
					 . Status::to_css_class($subm)
				);
				$this->write_submission($subm);
				$this->write_block_end();
				$i--;
			}
		}
		
	}
	
	// ---------------------------------------------------------------------
	// Failure details
	// ---------------------------------------------------------------------
	
	function write_testset_details($subm) {
		echo "<table class=\"testcase-details\">";
		// testcase output
		$testset = new TestSet($this->entity);
		foreach ($testset->test_cases() as $case) {
			// status, this is a bit of a hack, we should look at exit codes
			$case_status = "unknown";
			$diff_file = $subm->output_filename("$case.diff");
			if (!file_exists($diff_file)) {
				if (!file_exists($subm->output_filename("$case.out"))) {
					$class = 'skipped';
					$case_status = "Skipped";
				} else {
					$class = 'failed';
					$case_status = "Runtime error";
				}
			} else if (filesize($diff_file) > 0) {
				$class = 'failed';
				$failed = true;
				$runtime_error = false;
				$case_status = "Wrong output";
			} else {
				$class = 'passed';
				$case_status = "Passed";
			}
			
			// description/hint
			$desc = '';
			if ($class == 'failed') {
				$desc_file = $subm->input_filename("$case.desc");
				if (file_exists($desc_file)) {
					$desc = "Hint: " . file_get_contents($desc_file);
				} else {
					// TODO: description from attributes?
				}
			}
			
			// input/output/error downloads
			$downloads = '';
			if ($class != 'skipped' && $this->entity->show_input_output_for($case)) {
				if (file_exists($subm->input_filename("$case.in"))) {
					$downloads .= download_link($subm,"in/$case.in", 'input') . ' | ';
				}
				if (file_exists($subm->input_filename("$case.out"))) {
					$downloads .= download_link($subm,"in/$case.out",'expected output') . ' | ';
				}
				if (file_exists($subm->output_filename("$case.out"))) {
					$downloads .= download_link($subm,"out/$case.out",'your output') . ' | ';
				}
				if (file_exists($subm->output_filename("$case.diff"))) {
					$downloads .= download_link($subm,"out/$case.diff",'difference') . ' | ';
				}
			}
			if ($case_status == 'Runtime error' && $this->entity->show_runtime_errors_for($case)) {
				if (file_exists($subm->output_filename("$case.err"))) {
					$downloads .= download_link($subm,"out/$case.err",'error message') . ' | ';
				}
			}
			$downloads = substr($downloads,0,-3);
			
			// write it
			$rows  = 1 + (strlen($desc) > 0 ? 1 : 0) + (strlen($downloads) > 0 ? 1 : 0);
			echo "<tr class=\"$class\"><td rowspan=\"$rows\">Test case " . htmlspecialchars($case) . "</td><td><span>$case_status</span></td></tr>";
			if (strlen($desc) > 0)      echo "<tr><td>$desc</td></tr>";
			if (strlen($downloads) > 0) echo "<tr><td>$downloads</td></tr>";
		}
		echo "</table>";
	}
	
	// ---------------------------------------------------------------------
	// Non-Submitable page
	// ---------------------------------------------------------------------
	
	function write_overview_item($e) {
		if (!$e->visible()) return;
		$class = '';
		if ($e->attribute_bool('submitable')) {
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
