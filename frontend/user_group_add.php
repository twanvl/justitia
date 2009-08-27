<?php

require_once('../lib/bootstrap.inc');

// -----------------------------------------------------------------------------
// Add group user and redirect
// -----------------------------------------------------------------------------

Authentication::require_user();

if (isset($_REQUEST['add'])) {
	UserGroup::add_id(intval($_REQUEST['add']));
	Util::redirect(@$_REQUEST['redirect'] );
}

// -----------------------------------------------------------------------------
// Page for picking user
// -----------------------------------------------------------------------------

class Page extends Template {
	function title() {
		return "Add user to group";
	}
	
	function write_user_list() {
		$this->write_block_begin('User list');
		echo '<form action="user_group_add.php">';
		$this->write_form_preserve('redirect');
		echo '<label>Filter: <input type="text" name="filter" value="'.htmlspecialchars(@$_REQUEST['filter']).'"></label>';
		echo '<input type="submit" value="Show">';
		echo '</form>';
		
		if (!isset($_REQUEST['filter'])) return;
		$filter = '%' . @$_REQUEST['filter'] . '%';
		
		echo '<table class="user-list">'."\n";
		$users = User::all($filter);
		foreach($users as $user) {
			echo '<tr>';
			echo '<td>',htmlspecialchars($user->name_and_login()),'</td>';
			if (UserGroup::contains($user)) {
				echo '<td>[already in group]</td>';
			} else {
				echo '<td><a href="user_group_add.php?add='.$user->userid
						.'&amp;redirect='.urlencode($_REQUEST['redirect'])
						.'">[add to group]</a></td>';
			}
			echo "</tr>\n";
		}
		echo '</table>';
		$this->write_block_end();
	}
	
	function write_body() {
		echo '<a href="'.htmlspecialchars($_REQUEST['redirect']).'">&lt;- cancel and go back</a>';
		$this->write_user_list();
	}
	
}

$page = new Page();
$page->write();
