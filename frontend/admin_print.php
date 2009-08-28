<?php

require_once('../lib/bootstrap.inc');

// -----------------------------------------------------------------------------
// Print submissions
// -----------------------------------------------------------------------------

// -----------------------------------------------------------------------------
// Print submissions : selection page
// -----------------------------------------------------------------------------

class Page extends PageWithEntity {
	
	function __construct() {
		Authentication::require_admin();
		$this->is_admin_page = true;
		parent::__construct();
		
		// print?
		if (isset($_REQUEST['filled'])) {
			$this->write_print();
			exit();
		}
	}
	
	function title() {
		return "Print submissions for " . parent::title();
	}
	
	function nav_script($entity) {
		return 'admin_print.php';
	}
	
	function write_body() {
		if ($this->entity->submitable()) {
			$this->write_form();
		} else {
			$this->write_non_submitable();
		}
	}
	
	function write_non_submitable() {
		echo "<em>Files can not be submited here.</em>";
	}
	
	function write_form() {
		$format = 'html';
		$include_failed = false;
		
		$this->write_block_begin("Settings");
		
		$this->write_form_begin('admin_print.php' . $this->entity->path(), 'get');
		$this->write_form_hidden('filled',1);
		$this->write_form_table_begin();
		$this->write_form_table_field('text',    'user_filter',    'Only for users', @$_REQUEST['user_filter']);
		//$this->write_form_table_field('radio',   'format',         'Text output', $format=='text', ' value="text"');
		//$this->write_form_table_field('radio',   'format',         'HTML output', $format=='html', ' value="html"');
		$this->write_form_table_field('checkbox','include_failed', 'Include failed and pending submissions', $include_failed);
		$this->write_form_table_end();
		$this->write_form_end("Generate printout");
		
		$this->write_block_end();
		
		
		$this->write_block_begin("Tips");
		echo "<ul>";
		echo "<li>Disable all headers and footers in the <tt>File</tt> &rarr; <tt>Page Setup</tt> dialog (in Firefox)</li>";
		echo "</ul>";
		$this->write_block_end();
	}
	
	function write_print_body() {
		// for each userid => subm
		$subms = $this->entity->all_final_submissions();
		// make unique
		$unique_subms = array();
		foreach($subms as $subm) {
			$unique_subms[$subm->submissionid] = $subm;
		}
		// sort by users
		$by_name = array();
		foreach($unique_subms as $subm) {
			$users = $subm->users();
			$name_of_first_user = count($users > 0) ? $users[0]->sort_name() : '';
			$by_name[$name_of_first_user] = $subm;
		}
		ksort($by_name);
		// TODO: sort submissions by name
		foreach ($by_name as $subm) {
			$this->write_print_submission($subm);
		}
	}
	
	function write_print_submission($subm) {
		// include this submission?
		if (!isset($_REQUEST['include_failed'])) {
			if (!Status::is_passed($subm->status)) return;
		}
		// does it match a user filter?
		if (@$_REQUEST['user_filter'] != '') {
			$match = stripos(User::names_text($subm->users()), $_REQUEST['user_filter']);
			if ($match === false) return;
		}
		// print it
		echo '<div class="submission">';
		echo '<div class="submission-head">';
		echo "<table><tr><td>Submission</td><td>#" . $subm->submissionid . " for <tt>" . htmlspecialchars($subm->entity_path) . "</tt></td></tr>";
		echo        "<tr><td>by</td><td>" . User::names_html($subm->users()) . "</td></tr>";
		echo        "<tr><td>on</td><td>" . format_date($subm->time)         . "</td></tr>";
		if (!Status::is_passed($subm->status)) {
			echo "<tr><td>status</td><td>" . Status::to_text($subm) . "</td></tr>";
		}
		echo "</table>";
		echo "</div>\n";
		$this->write_print_file($subm->filename,$subm->get_file($subm->code_filename()));
		echo '</div>';
	}
	function write_print_file($filename,$contents) {
		echo '<div class="file-head">';
		echo htmlspecialchars($filename);
		echo '</div>';
		$contents = str_replace("\r","",$contents);
		
		echo '<pre>';
		$lines = explode("\n",$contents);
		foreach($lines as $line) {
			list($indent,$rest) = Page::take_indent($line);
			echo '<div class="line">';
			echo '<span class="indent">'.$indent.'</span>';
			echo '<span class="rest">'.htmlspecialchars($rest)."\n".'</span>';
			echo "</div>";
		}
		echo '</pre>';
		
		//$content_html = htmlspecialchars($contents);
		//echo "<pre class='simple'>$content_html</pre>";
	}
	static function take_indent($line) {
		$len = strlen($line);
		$indent = '';
		for ($i = 0 ; $i < $len ; ++$i) {
			if ($line{$i} == ' ') {
				$indent .= ' ';
			} else if ($line{$i} == "\t") {
				$indent .= '<b>    </b>';
			} else {
				break;
			}
		}
		return array($indent,substr($line,$i));
	}
}

$page = new Page();
$page->write();
