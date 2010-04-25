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
		return 'admin_print2.php';
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
		echo "<li>The 'include files' filter is a <a href=\"http://php.net/manual/en/reference.pcre.pattern.syntax.php\">regular expression</a>. For example, to exclude files use <tt>^(?!foo)</tt></li>";
		echo "<li>Disable all headers and footers in the <tt>File</tt> &rarr; <tt>Page Setup</tt> dialog.</li>";
		echo "<li>Double sided printing only works in Opera.</li>";
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
		echo '<pre>';
		echo "\\documentclass[a4paper,10pt,twoside]{article}\n";
		echo "\\usepackage[dutch]{babel}\n";
		echo "\\usepackage{graphicx}\n";
		echo "\\usepackage{listings}\n";
		echo "\\usepackage{color}\n";
		echo "\\usepackage[hmargin=1.5cm,vmargin=2.5cm]{geometry}\n";
		echo "\\pagestyle{empty}\n";
		echo "\\definecolor{MyGray}{rgb}{0.95,0.95,0.95}\n";
		echo "\\RequirePackage{pifont}\n";
		echo "\\lstset{";
		echo "	language=C,";
		echo "	tabsize=3,";
		echo "	tab=\$\\longrightarrow\$,";
		echo "	showstringspaces=false,";
		echo "	breaklines=true,";
		echo "	basicstyle=\\sffamily,";
		echo "	keywordstyle=\\bfseries,";
		echo "	commentstyle=\\itshape,";
		echo "	columns=fullflexible,";
		echo "	stepnumber=5,firstnumber=0,numbers=left,";
		echo "	numberstyle=\\scriptsize,";
		echo "	postbreak=\\makebox[0pt][r]{\\color[rgb]{0.7,0.7,0.7}{\\ding{229}}\\hspace*{0.45em}},";
		echo "	backgroundcolor=\\color[rgb]{0.9,0.9,0.9},";
		echo "	frame=lines,";
		echo "	framesep=0pt,";
		echo "}\n";

		echo "\\begin{document}\n";

		// print each submission
		foreach ($by_name as $subm) {
			$this->write_print_submission($subm);
		}
		echo '\\end{document}</pre>';
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
		
		// submission header
		echo "\section*{Submission \\#" . $subm->submissionid . " for " . htmlspecialchars($subm->entity_path) . "}\n";
		echo        "by " . User::names_html($subm->users()) . ",";
		echo        "on " . format_date($subm->time)         . "\n\n";
		if (!Status::is_passed($subm->status)) {
			echo "(status " . Status::to_text($subm) . ")";
		}
		
		// submission files
		foreach ($subm->get_code_filenames() as $code_name => $filename) {
			$this->write_print_file($filename, $subm->get_file($code_name));
		}
		
		echo "\\cleardoublepage\n";
	}
	
	function latexspecialchars($s){
		$s = preg_replace("/_/","\\_",$s);
		return htmlspecialchars($s);
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
		echo "\subsection*{";
		echo $this->latexspecialchars($filename);
		if ($class == 'file skipped') echo ' (skipped)';
		echo "}\n\n";
		$contents = str_replace("\r","",$contents);
		echo "\\begin{lstlisting}\n";
		// file contents
		$lines = explode("\n",$contents);
		foreach($lines as $line) {
			if ($this->tab_replacement == "\t") {
				echo htmlspecialchars($line) . "\n";
			} else {
				list($indent,$rest) = $this->take_indent($line);
				echo htmlspecialchars($indent)."";
				echo htmlspecialchars($rest)."\n";
			}
		}
		echo "\\end{lstlisting}\n";
	}
	
	// Split a line into  array(indentation,rest)
	function take_indent($line) {
		$len = strlen($line);
		$indent = '';
		for ($i = 0 ; $i < $len ; ++$i) {
			if ($line{$i} == ' ') {
				$indent .= ' ';
			} else if ($line{$i} == "\t") {
				$indent .= "$this->tab_replacement";
			} else {
				break;
			}
		}
		return array($indent,substr($line,$i));
	}
}

$view = new View();
$view->write();
