<?php

require_once('../lib/bootstrap.inc');

// -----------------------------------------------------------------------------
// Print submissions
// -----------------------------------------------------------------------------

// -----------------------------------------------------------------------------
// Print submissions : selection page
// -----------------------------------------------------------------------------

class View extends PageWithEntity {
	
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
	
	// ---------------------------------------------------------------------
	// Form for printout options
	// ---------------------------------------------------------------------
	
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
		$double_sided = true;
		
		$this->write_block_begin("Settings");
		
		$this->write_form_begin('admin_print.php' . $this->entity->path(), 'get');
		$this->write_form_hidden('filled',1);
		$this->write_form_table_begin();
		$this->write_form_table_field('text',    'user_filter',    'Only for users', @$_REQUEST['user_filter']);
		$this->write_form_table_field('text',    'filename_filter','Include files', @$_REQUEST['filename_filter']);
		//$this->write_form_table_field('radio',   'format',         'Text output', $format=='text', ' value="text"');
		//$this->write_form_table_field('radio',   'format',         'HTML output', $format=='html', ' value="html"');
		$this->write_form_table_field('checkbox','include_failed', 'Include failed and pending submissions', $include_failed);
		$this->write_form_table_field('radio',   'tabsize',        'Tabs', 4, array(
			4 => 'Replace by 4 spaces<br>',
			8 => 'Replace by 8 spaces<br>',
			0 => 'Keep as tabs',
		));
		$this->write_form_table_field('checkbox','double_sided',    'Double sided printing (ensure that each submission starts on an odd page)', $double_sided);
		$this->write_form_table_end();
		$this->write_form_end("Generate printout");
		
		$this->write_block_end();
		
		
		$this->write_block_begin("Tips");
		echo "<ul>";
		echo "<li>The 'include files' filter is a <a href=\"http://php.net/manual/en/reference.pcre.pattern.syntax.php\">regular expression</a>. For example, to exclude files use <tt>^(?!foo)</tt>.</li>";
		echo "<li>Disable all headers and footers in the <tt>File</tt> &rarr; <tt>Page Setup</tt> dialog.</li>";
		echo "<li>Double sided printing only works in Opera.</li>";
		echo "<li>Submissions that ware made after the deadline will also be printed, but will be marked as <em>missed deadline</em>.</li>";
		echo "</ul>";
		$this->write_block_end();
	}
	
	// ---------------------------------------------------------------------
	// The printout
	// ---------------------------------------------------------------------
	
	private $tab_replacement = '    ';
	
	function write_print_body() {
		// number of spaces in a tab
		if (isset($_REQUEST['tabsize'])) {
			$tabsize = intval($_REQUEST['tabsize']);
			if ($tabsize == 0) {
				$this->tab_replacement = "\t";
			} else {
				$this->tab_replacement = '';
				for ($i = 0 ; $i < $tabsize ; ++$i) $this->tab_replacement .= ' ';
			}
		}
		
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
			$name_of_first_user = User::names_for_sort($users);
			$by_name[$name_of_first_user] = $subm;
		}
		ksort($by_name);
		
		// print each submission
		foreach ($by_name as $subm) {
			$this->write_print_submission($subm);
		}
	}
	
	function write_print_submission($subm) {
		// include this submission?
		if (!isset($_REQUEST['include_failed'])) {
			if (!Status::is_passed($subm->status) AND !$subm->status == Status::MISSED_DEADLINE) return;
		}
		// does it match a user filter?
		if (@$_REQUEST['user_filter'] != '') {
			$match = stripos(User::names_text($subm->users()), $_REQUEST['user_filter']);
			if ($match === false) return;
		}
		
		// submission header
		echo '<div class="submission">';
		echo '<div class="submission-head">';
		echo "<table><tr><td>Submission</td><td>#" . $subm->submissionid . " for <tt>" . htmlspecialchars($subm->entity_path) . "</tt></td></tr>";
		echo        "<tr><td>by</td><td>" . User::names_html($subm->users()) . "</td></tr>";
		echo        "<tr><td>on</td><td>" . format_date($subm->time)         . "</td></tr>";
		if (!Status::is_passed($subm->status)) {
			echo "<tr><td>status</td><td><strong>" . strtoupper(Status::to_text($subm)) . "</strong></td></tr>";
		}
		echo "</table>";
		echo "</div>\n";
		
		// submission files
		foreach ($subm->get_code_filenames() as $code_name => $filename) {
			$this->write_print_file($filename, $subm->get_file($code_name));
		}
		
		echo "</div>\n";
	}
	
	function write_print_file($filename,$contents) {
		// include this file?
		$class = 'file';
		if (@$_REQUEST['filename_filter'] != '') {
			if (!preg_match("@" . @$_REQUEST['filename_filter'] . "@", $filename)) {
				$class = 'file skipped';
			}
		}
		
		// file header
		echo "<div class=\"$class\">";
		echo '<div class="file-head">';
		echo htmlspecialchars($filename);
		if ($class == 'file skipped') echo ' <span>(skipped)</span>';
		echo '</div>';
		$contents = str_replace("\r","",$contents);
		
		// file contents
		echo '<pre>';
		$lines = explode("\n",$contents);
		foreach($lines as $line) {
			if ($this->tab_replacement == "\t") {
				echo htmlspecialchars($line) . "\n";
			} else {
				list($indent,$rest) = $this->take_indent($line);
				echo '<div class="line">';
				echo '<span class="indent">'.$indent.'</span>';
				echo '<span class="rest">'.htmlspecialchars($rest)."\n".'</span>';
				echo "</div>";
			}
		}
		echo '</pre>';
		echo '</div>';
	}
	
	// Split a line into  array(indentation,rest)
	function take_indent($line) {
		$len = strlen($line);
		$indent = '';
		for ($i = 0 ; $i < $len ; ++$i) {
			if ($line{$i} == ' ') {
				$indent .= ' ';
			} else if ($line{$i} == "\t") {
				$indent .= "<b>$this->tab_replacement</b>";
			} else {
				break;
			}
		}
		return array($indent,substr($line,$i));
	}
	
	
	function write_skipped_file($filename) {
		echo '<div class="skipped-file-head">';
		echo htmlspecialchars($filename);
		echo ' <span>(skipped)</span>';
		echo '</div>';
	}
}

$view = new View();
$view->write();
