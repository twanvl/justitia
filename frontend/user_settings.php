<?php

require_once('../lib/bootstrap.inc');

// -----------------------------------------------------------------------------
// Personal user administration
// -----------------------------------------------------------------------------

// TODO: move to library
function get_request_data(&$data, $prefix, $name) {
	if (isset($_REQUEST[$prefix.$name])) {
		$data[$name] = $_REQUEST[$prefix.$name];
	}
}

class View extends Template {
	private $user;
	
	function __construct() {
		$this->user = Authentication::require_user();
	}
	
	function title() {
		return "Settings for " . $this->user->login;
	}
	
	
	function write_edit_this_user() {
		$data = $this->user->data();
		
		if (@$_REQUEST['filled']) {
			$data['password'] = $data['password2'] = '';
			get_request_data($data,'user_','old_password');
			get_request_data($data,'user_','password');
			get_request_data($data,'user_','password2');
			
			// validate
			if (!$this->user->check_password($data['old_password'],false)) {
				$this->add_message('user','error',"Old password does not match.");
			} else if (strlen($data['password']) < 5) {
				$this->add_message('user','error',"New password too short.");
			} else if ($data['password'] != $data['password2']) {
				$this->add_message('user','error',"New password does not match confirmation.");
			}
			unset($data['password2']);
			unset($data['old_password']);
			// update
			if (!$this->has_messages('user')) {
				try {
					$this->user->alter($data);
					$this->add_message('user','confirm',"Password updated");
				} catch (Exception $e) {
					$this->add_message('user','error',$e->getMessage());
				}
			}
		}
		
		$this->write_block_begin('Change password');
		$this->write_messages('user');
		$this->write_form_begin('user_settings.php','post');
		$this->write_form_preserve('redirect');
		$this->write_form_hidden('filled',1);
		$this->write_form_table_begin();
		$this->write_form_table_data('Login',        $data['login']);
		$this->write_form_table_data('First name',   $data['firstname']);
		$this->write_form_table_data('Middle name',  $data['midname']);
		$this->write_form_table_data('Last name',    $data['lastname']);
		$this->write_form_table_data('Email',        $data['email']);
		$this->write_form_table_data('Class',        $data['class']);
		$this->write_form_table_field('password','user_old_password', 'Old password');
		$this->write_form_table_field('password','user_password',     'Password');
		$this->write_form_table_field('password','user_password2',    'Confirm password');
		$this->write_form_table_end();
		$this->write_form_end('Update');
		$this->write_block_end();
	}
	
	function write_body() {
		echo '<a href="'.htmlspecialchars(@$_REQUEST['redirect']).'">&larr; cancel and go back</a>';
		$this->write_edit_this_user();
	}
}

$view = new View();
$view->write();
