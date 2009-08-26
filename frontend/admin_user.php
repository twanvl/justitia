<?php

require_once('./bootstrap.inc');

// -----------------------------------------------------------------------------
// User administration
// -----------------------------------------------------------------------------

class Page extends Template {
	private $entity;
	
	function __construct() {
		// find active entity
		Authentication::require_admin();
	}
	
	function title() {
		return "User administration";
	}
	
	function write_form_preserve($what) {
		if (!isset($_REQUEST[$what])) return;
		echo '<input type="hidden" name="'.$what.'" value="'
		    . htmlspecialchars($_REQUEST[$what]) . '">';
	}
	function write_form_table_field($type, $name, $label, $value) {
		echo "<tr><td><label for=\"$name\">$label</label></td>\n";
		echo "    <td><input type=\"$type\" id=\"$name\" name=\"$name\" value=\"". htmlspecialchars($value) ."\"></td></tr>\n";
	}
	
	function write_edit_user() {
		$editing = isset($_REQUEST['edit']);
		if ($editing) {
			$user = User::by_login($_REQUEST['edit']);
			$data = $user->data;
			echo '<h2>Edit user: '.htmlspecialchars($user->login).'</h2>';
		} else {
			echo '<h2>Add user</h2>';
			$data = array();
		}
		
?>
  <form action="admin_user.php" method="get">
  <?php $this->write_form_preserve('filter');
        $this->write_form_preserve('edit'); ?>
  <table>
    <?php $this->write_form_table_field('text',    'user_login',    'Login',   $data['login']); ?>
    <?php $this->write_form_table_field('password','user_password', 'Password', ''); ?>
    <?php $this->write_form_table_field('password','user_password2','Confirm password', ''); ?>
    <?php $this->write_form_table_field('text',    'user_firstname','First name',   $data['firstname']); ?>
    <?php $this->write_form_table_field('text',    'user_midname',  'Middle name',  $data['midname']); ?>
    <?php $this->write_form_table_field('text',    'user_lastname', 'Last name',    $data['lastname']); ?>
  </table>
  <input type="submit" value="Add user">
</form><?php
	}
	
	function write_user_list() {
		echo '<h2>User list</h2>';
		echo '<td><form action="admin_user.php">'.
		           '<label>Filter: <input type="text" name="filter" value="'.htmlspecialchars(@$_REQUEST['filter']).'"></label>'.
		           '<input type="submit" value="Show">'.
		          '</form></td>';
		
		if (!isset($_REQUEST['filter'])) return;
		$filter = '%' . @$_REQUEST['filter'] . '%';
		
		echo '<table class="user-list">'."\n";
		echo "<tr>";
		echo "<th>Login</th>";
		echo "<th>Name</th>";
		echo "<th>Admin?</th>";
		echo "</tr>\n";
		
		$users = User::all($filter);
		foreach($users as $user) {
			echo '<tr>';
			echo '<td>',htmlspecialchars($user->login),'</td>';
			echo '<td>',htmlspecialchars($user->name()),'</td>';
			echo '<td>',($user->is_admin?'yes':''),'</td>';
			echo '<td><a href="admin_user.php?edit='.htmlspecialchars($user->login)
			                               .'&amp;filter='.htmlspecialchars($_REQUEST['filter'])
			                               .'">edit</a></td>';
			/*echo '<td><form action="admin_user.php">'.
			           '<input type="hidden" name="delete" value="'.htmlspecialchars($user->login).'">'.
			           '<input type="submit" value="Delete">'.
			          '</form></td>';*/
			echo "</tr>\n";
		}
		echo '</table>';
	}
	
	function write_body() {
		$this->write_edit_user();
		echo "<hr>\n";
		$this->write_user_list();
	}
	
}
new Page();
