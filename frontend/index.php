<?php

require_once('./bootstrap.inc');
require_once('./submit.inc');

// -----------------------------------------------------------------------------
// Main 'entity' page
// -----------------------------------------------------------------------------

function format_bool($b) {
	return $b ? "yes" : "no";
}

class Page extends Template {
	private $entity;
	
	function __construct() {
		// find active entity
		Authentication::require_user();
		$this->entity = Entity::get(@$_SERVER['PATH_INFO']);
		handle_uploaded_submission($this->entity);
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
	// Submitable page
	// ---------------------------------------------------------------------
	
	function write_submit_form() {
		$this->write_messages('submit');
?><form action="index.php<?php echo $this->entity->path(); ?>" method="post" enctype="multipart/form-data">
  <label>Select file</label> <input type="file" name="file" id="file"><br>
  <input type="submit" name="submit" value="Submit" id="submit">
</form>
<script type="text/javascript">
<!--
  var file_control = document.getElementById('file');
  file_control.onchange = function() {
	var ok = file_control.value.match(/<?php echo ".*\\.(java|c)"; ?>/);
	document.getElementById('submit').style.backgroundColor = ok ? 'white' : 'red';
  }
//-->
</script><?php
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
		$path = "download.php/" . $subm->submissionid . '/' . $subm->file_name;
		echo "<table>";
		echo "<tr><td>Submitted on</td><td>" . format_date($subm->time) . "</td>";
		echo "<tr><td>Submitted by</td><td>" . User::names_html($subm->users()) . "</td>";
		echo '<tr><td>Files</td><td><a href="'.$path.'">Download submitted files</a></td>';
		echo "<tr><td>Status</td><td>"       . Status::to_text($subm) . "</td>";
		echo "</table>";
	}
	
	function write_submitable_page() {
		$submissions = Authentication::current_user()->submissions_to($this->entity);
		
		$this->write_block_begin('Problem description');
		$this->write_submitable_entity_info();
		$this->write_block_end();
		
		// submission form
		$last_submission = Authentication::current_user()->last_submission_to($this->entity);
		$passed = Status::is_passed(Status::to_status($last_submission));
		$this->write_block_begin('Submit', 'collapsable block' . ($passed ? ' collapsed' : ''));
		$this->write_submit_form();
		$this->write_block_end();
		
		echo "<h2>Submissions</h2>";
		
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
	// Non-Submitable page
	// ---------------------------------------------------------------------
	
	function write_overview_item($e) {
		$class = '';
		if ($e->attribute_bool('submitable')) {
			$subm = Authentication::current_user()->status_of_last_submission_to($e);
			$class .= Status::to_css_class($subm) . ' ';
		}
		if (!$e->active()) {
			$class .= 'inactive ';
		}
		$this->write_block_begin($e->title(), 'collapsed block '.$class, 'index.php' . $e->path());
		$this->write_block_end();
	}
	function write_overview_page() {
		foreach ($this->entity->children() as $e) {
			$this->write_overview_item($e);
		}
	}

	// -----------------------------------------------------------------------------
	// Directory listing
	// -----------------------------------------------------------------------------

	//require_once('template.inc');

	function write_tree($e) {
		echo "<ul>";
		echo "<pre>"; print_r($e->attributes()); echo "</pre>";
		echo "<pre>"; print_r(Authentication::current_user()->submissions_to($e)); echo "</pre>";
		foreach($e->children() as $n => $d) {
			echo "<li><a href='index.php". $d->path() ."'>" . htmlspecialchars($d->title()) .  "</a>";
			echo $d->visible()    ? 'V+ ' : 'V- ' ;
			echo $d->active() ? 'A+ ' : 'A- ' ;
			//echo $d->submitable() ? 'S+ ' : 'S- ' ;
			write_tree($d);
			
			echo "</li>";
		}
		echo "</ul>";
	}

	//write_tree(Entity::get_root());
	//write_tree(Entity::get(""));


	function write_nav_tree($here) {
		echo "<ul>";
		foreach ($here->ancestors() as $e) {
			echo "<li>";
			echo '<a href="index.php' . $e->path() .'">' . htmlspecialchars($e->title()) . '</a>';
			echo "</li>";
		}
		echo "</ul>";
	}

}

$page = new Page();
$page->write();
