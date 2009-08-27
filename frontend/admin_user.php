<?php

require_once('./bootstrap.inc');

// -----------------------------------------------------------------------------
// User administration
// -----------------------------------------------------------------------------

function get_request_data(&$data, $prefix, $name) {
	if (isset($_REQUEST[$prefix.$name])) {
		$data[$name] = $_REQUEST[$prefix.$name];
	}
}
function get_request_bool(&$data, $prefix, $name) {
	$data[$name] = isset($_REQUEST[$prefix.$name]);
}

class Page extends Template {
	function __construct() {
		Authentication::require_admin();
	}
	
	function title() {
		return "User administration";
	}
	
	function write_edit_user() {
		$editing = isset($_REQUEST['edit']);
		
		if ($editing) {
			$user = User::by_id($_REQUEST['edit']);
			$data = $user->data();
		} else {
			$data = array(
				'login'     => '',
				'firstname' => '',
				'midname'   => '',
				'lastname'  => '',
				'is_admin'  => false,
			);
		}
		
		if (@$_REQUEST['filled']) {
			$data['password'] = $data['password2'] = '';
			get_request_data($data,'user_','login');
			get_request_data($data,'user_','password');
			get_request_data($data,'user_','password2');
			get_request_data($data,'user_','firstname');
			get_request_data($data,'user_','midname');
			get_request_data($data,'user_','lastname');
			get_request_bool($data,'user_','is_admin');
			
			// validate
			if (($data['password'] == '' || $data['password2'] == '') && $editing) {
				unset($data['password']);
			} else if (strlen($data['password']) < 5) {
				$this->add_message('user','error',"Password too short");
			} else if ($data['password'] != $data['password2']) {
				$this->add_message('user','error',"Passwords do not match");
			}
			unset($data['password2']);
			if (strlen($data['login']) < 3) {
				$this->add_message('user','error',"Login too short");
			}
			if (strlen($data['firstname']) < 1) {
				$this->add_message('user','error',"Enter a first name");
			}
			if (strlen($data['lastname']) < 1) {
				$this->add_message('user','error',"Enter a last name");
			}
			
			// add/update
			if (!$this->has_messages('user')) {
				try {
					if ($editing) {
						$user->alter($data);
						$this->add_message('user','confirm',"User updated");
					} else {
						$user = User::add($data);
						$editing = $user->userid;
						$this->add_message('user','confirm',"User created");
						$data = $user->data();
					}
				} catch (Exception $e) {
					$this->add_message('user','error',$e->getMessage());
				}
			}
		}
		
		// show form
		if ($editing) {
			$this->write_block_begin('Edit user: '. $user->login);
		} else {
			$this->write_block_begin('Add user');
		}
		$this->write_messages('user');
		?><form action="admin_user.php" method="get">
		  <?php $this->write_form_preserve('filter');
		        $this->write_form_preserve('edit');
		        $this->write_form_hidden('filled',1); ?>
		  <table>
		    <?php $this->write_form_table_field('text',    'user_login',    'Login',        $data['login']); ?>
		    <?php $this->write_form_table_field('password','user_password', 'Password'); ?>
		    <?php $this->write_form_table_field('password','user_password2','Confirm password'); ?>
		    <?php $this->write_form_table_field('text',    'user_firstname','First name',   $data['firstname']); ?>
		    <?php $this->write_form_table_field('text',    'user_midname',  'Middle name',  $data['midname']); ?>
		    <?php $this->write_form_table_field('text',    'user_lastname', 'Last name',    $data['lastname']); ?>
		    <?php $this->write_form_table_field('checkbox','user_is_admin', 'Administrator',$data['is_admin']); ?>
		  </table>
		  <input type="submit" value="<?php echo $editing ? 'Update user' : 'Add user'; ?>">
		</form><?php
		$this->write_block_end();
	}
	
	function write_user_list() {
		$this->write_block_begin('User list');
		echo '<form action="admin_user.php">';
		echo '<label>Filter: <input type="text" name="filter" value="'.htmlspecialchars(@$_REQUEST['filter']).'"></label>';
		echo '<input type="submit" value="Show">';
		echo '</form>';
		
		if (!isset($_REQUEST['filter'])) return;
		$filter = '%' . @$_REQUEST['filter'] . '%';
		
		echo '<table class="user-list">'."\n";
		echo "<thead><tr>";
		echo "<th>Login</th>";
		echo "<th>Name</th>";
		echo "<th>Admin?</th>";
		echo "</tr></thead><tbody>\n";
		
		$users = User::all($filter);
		foreach($users as $user) {
			echo '<tr>';
			echo '<td>',htmlspecialchars($user->login),'</td>';
			echo '<td>',htmlspecialchars($user->name()),'</td>';
			echo '<td>',($user->is_admin?'yes':''),'</td>';
			echo '<td><a href="admin_user.php?edit='.$user->userid
			                               .'&amp;filter='.urlencode($_REQUEST['filter'])
			                               .'">edit</a></td>';
			echo "</tr>\n";
		}
		echo '</tbody></table>';
		$this->write_block_end();
	}
	
	function write_body() {
		$this->write_edit_user();
		$this->write_user_list();
	}
	
}

$page = new Page();
$page->write();
