<?php

require_once('../lib/bootstrap.inc');

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

class View extends Template {
	function __construct() {
		Authentication::require_admin();
		$this->is_admin_page = true;
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
				'email'     => '',
				'class'     => '',
				'notes'     => '',
				'is_admin'  => false,
				'auth_method' => 'pass',
			);
		}
		
		if (@$_REQUEST['filled']) {
			$data['password'] = $data['password2'] = '';
			get_request_data($data,'user_','login');
			get_request_data($data,'user_','auth_method');
			get_request_data($data,'user_','password');
			get_request_data($data,'user_','password2');
			get_request_data($data,'user_','firstname');
			get_request_data($data,'user_','midname');
			get_request_data($data,'user_','lastname');
			get_request_data($data,'user_','email');
			get_request_data($data,'user_','class');
			get_request_data($data,'user_','notes');
			get_request_bool($data,'user_','is_admin');
			
			// validate
			if (($data['password'] == '' || $data['password2'] == '') && $editing) {
				unset($data['password']);
			} else {
				if ($data['auth_method'] == 'pass') {
					if (strlen($data['password']) < 5) {
						$this->add_message('user','error',"Password too short");
					} else if ($data['password'] != $data['password2']) {
						$this->add_message('user','error',"Passwords do not match");
					}
				}
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
			$this->write_block_begin('Add user', 'collapsable block' . (@$_REQUEST['filled'] ? '' : ' collapsed'));
		}
		
		$this->write_messages('user');
		$this->write_form_begin('admin_user.php','post');
		$this->write_form_preserve('user_filter');
		$this->write_form_preserve('edit');
		$this->write_form_hidden('filled',1);
		$this->write_form_table_begin();
		$this->write_form_table_field('text',    'user_login',    'Login',        $data['login']);
		$this->write_form_table_field('checkbox','user_is_admin', 'Administrator',$data['is_admin']);
		$this->write_form_table_field('radio',   'user_auth_method', 'Authentication', $data['auth_method'], array(
				'pass' => 'Log in with password',
				'ldap' => 'Log in via LDAP (central password)',
			));
		$this->write_form_table_field('password','user_password', 'Password');
		$this->write_form_table_field('password','user_password2','Confirm password');
		$this->write_form_table_field('text',    'user_firstname','First name',   $data['firstname']);
		$this->write_form_table_field('text',    'user_midname',  'Middle name',  $data['midname'], ' size="5"');
		$this->write_form_table_field('text',    'user_lastname', 'Last name',    $data['lastname']);
		$this->write_form_table_field('text',    'user_email',    'Email address',$data['email']);
		$this->write_form_table_field('text',    'user_class',    'Class',        $data['class']);
		$this->write_form_table_field('textarea','user_notes',    'Notes',        $data['notes'], ' cols="60" rows="4"');
		$this->write_form_table_end();
		$this->write_form_end($editing ? 'Update user' : 'Add user');
		$this->write_block_end();
	}
	
	function write_user_list() {
		$this->write_block_begin('User list');
		
		$this->write_form_begin('admin_user.php','get');
		echo '<label>Search for users: ';
		$this->write_form_field('text','user_filter',@$_REQUEST['filter'],' size="35"');
		echo '</label> ';
		$this->write_form_end('Show');
		
		if (!isset($_REQUEST['user_filter'])) return;
		$filter = '%' . @$_REQUEST['user_filter'] . '%';
		
		echo '<table class="user-list">'."\n";
		echo "<thead><tr>";
		echo "<th>Login</th>";
		echo "<th>Name</th>";
		echo "<th>Email</th>";
		echo "<th>Admin?</th>";
		echo "</tr></thead><tbody>\n";
		
		$users = User::all($filter);
		foreach($users as $user) {
			echo '<tr>';
			echo '<td>',htmlspecialchars($user->login),'</td>';
			echo '<td>',htmlspecialchars($user->name()),'</td>';
			if ($user->email) {
				echo '<td><a href="mailto:',htmlspecialchars($user->email),'">',htmlspecialchars($user->email),'</a></td>';
			} else {
				echo '<td></td>';
			}
			echo '<td>',($user->is_admin?'yes':''),'</td>';
			echo '<td><a href="admin_user.php?edit='.$user->userid
			                               .'&amp;user_filter='.urlencode($_REQUEST['user_filter'])
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

$view = new View();
$view->write();
