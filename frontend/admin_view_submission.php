<?php

require_once('../lib/bootstrap.inc');
require_once('./submission_view.inc');

// -----------------------------------------------------------------------------
// View a single submission
// -----------------------------------------------------------------------------

class Page extends PageWithEntity {
	private $subm;
	
	function __construct() {
		Authentication::require_admin();
		$this->is_admin_page = true;
		// find submission
		try {
			$this->subm = Submission::by_id(intval($_REQUEST['submissionid']));
		} catch (Exception $e) {
			ErrorPage::die_fancy($e->getMessage());
		}
		$this->entity = $this->subm->entity();
		// rejudge?
		if (isset($_REQUEST['rejudge'])) {
			$this->subm->rejudge();
			// redirect to this page, so a refresh doesn't rejudge again
			Util::redirect('admin_view_submission.php?submissionid=' . $_REQUEST['submissionid']);
		}
		// delete?
		if (isset($_REQUEST['delete'],$_POST['confirm']) && $_POST['confirm'] == sha1('confirmed'.$this->subm->submissionid)) {
			$this->subm->delete();
			Util::redirect('index.php' . $this->entity->path());
		}
	}
	
	function title() {
		return "View submission #" . $this->subm->submissionid;
	}
	
	function write_body() {
		if (isset($_REQUEST['delete'])) {
			$this->write_block_begin("Confirm deletion");
			echo "This action can not be undone!<br>";
			$this->write_form_begin('admin_view_submission.php', 'post');
			$this->write_form_preserve('submissionid');
			$this->write_form_hidden('delete',1);
			$this->write_form_hidden('confirm',sha1('confirmed'.$this->subm->submissionid));
			$this->write_form_end("Delete submission");
			$this->write_block_end();
		}
		$this->write_the_submission();
	}
	
	function write_the_submission() {
		if (isset($_REQUEST['redirect'])) {
			echo '<a href="'.htmlspecialchars($_REQUEST['redirect']).'">&larr; back</a>';
		}
		$this->write_block_begin(
			"Submission",
			'block submission ' . Status::to_css_class($this->subm)
		);
		write_submission($this->subm,$this->entity,true);
		$this->write_block_end();
	}
}

$page = new Page();
$page->write();
