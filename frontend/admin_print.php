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
	}
	
	function title() {
		return "Print submissions for " . parent::title();
	}
	
	function write_body() {
		$this->write_form();
		$this->write_print_output();
	}
	
	function write_form() {
		$format = 'html';
		$include_failed = false;
		
		$this->write_block_begin("Settings");
		
		$this->write_form_begin('admin_print.php' . $this->entity->path(), 'get');
		$this->write_form_table_begin();
		$this->write_form_table_field('text',    'user_filter',    'Only for users', @$_REQUEST['user_filter']);
		$this->write_form_table_field('radio',   'format',         'Text output', $format=='text', ' value="text"');
		$this->write_form_table_field('radio',   'format',         'HTML output', $format=='html', ' value="html"');
		$this->write_form_table_field('checkbox','include_failed', 'Include failed submissions', $include_failed);
		$this->write_form_table_end();
		$this->write_form_end("Go");
		
		$this->write_block_end();
	}
	
	function write_print_output() {
		echo '<div class="print">';
		// for each userid => subm
		$subms = $this->entity->all_final_submissions();
		$unique_subms = array_unique($subms,SORT_REGULAR);
		foreach ($unique_subms as $subm) {
			$this->write_print_submission($subm);
		}
		echo '</div>';
	}
	function write_print_submission($subm) {
		echo '<div class="submission">';
		echo '<div class="submission-head">';
		echo "<table><tr><td>Submission</td><td>#" . $subm->submissionid . " for " . htmlspecialchars($subm->entity_path) . "</td></tr>";
		echo        "<tr><td>by</td><td>" . User::names_html($subm->users()) . "</td></tr>";
		echo        "<tr><td>on</td><td>" . format_date($subm->time)         . "</td></tr></table>";
		echo '</div>';
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
