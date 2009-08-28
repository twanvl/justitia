<?php

require_once('../lib/bootstrap.inc');

// -----------------------------------------------------------------------------
// Results overview table
// -----------------------------------------------------------------------------

class Page extends PageWithEntity {
	
	function __construct() {
		Authentication::require_admin();
		$this->is_admin_page = true;
		// find active entity
		parent::__construct();
	}
	
	function title() {
		return "Results for " . parent::title();
	}
	
	function nav_script($entity) {
		return 'admin_results.php';
	}
	
	function write_body() {
		function is_submitable($e) {
			return $e->submitable();
		}
		$entities = array_filter($this->entity->descendants(),'is_submitable');
		$this->write_get_submission_results($entities);
	}
	
	function write_get_submission_results($entities) {
		// statistics
		$num_submissions = 0;
		$num_passed = 0;
		$num_failed = 0;
		// find submissions
		$users = array();
		foreach($entities as $e => $entity) {
			// make a mapping userid => subm
			$subms = $entity->all_submissions();
			foreach ($subms as $subm) {
				$userids = $subm->userids();
				foreach($userids as $userid) {
					if (!isset($users[$userid])) {
						$users[$userid] = array(
							'subms' => array(),
							'user'  => User::by_id($userid)
						);
					}
					// keep the last/best one
					$old_subm = @$users[$userid]['subms'][$e];
					if ($old_subm) {
						if ($entity->attribute_bool('keep best')) {
							$use = $subm->status >= $old_subm->status;
						} else {
							$use = true;
						}
					} else {
						$use = true;
					}
					// is this it?
					if ($use) $users[$userid]['subms'][$e] = $subm;;
				}
				$num_submissions++;
				if (Status::is_passed($subm->status)) $num_passed++;
				if (Status::is_failed($subm->status)) $num_failed++;
			}
		}
		// sort users by name
		$users_sorted = array();
		foreach($users as $user) {
			$users_sorted[$user['user']->sort_name()] = $user;
		}
		ksort($users_sorted);
		// write statistics
		echo "<table>\n";
		echo "<tr><th>Number of submissions</th><td>$num_submissions</td>";
		echo "<tr><th>Number passed</th><td>$num_passed</td>";
		echo "<tr><th>Number failed</th><td>$num_failed</td>";
		echo "</table>\n";
		// write table
		$this->write_submission_results($entities,$users_sorted);
	}
	
	// write a table, given
	//  mapping userid -> array(submission,submission,..)
	function write_submission_results($entities,$users) {
		echo "<table class=\"results\">\n";
		// heading
		echo "<tr><thead><th>User</th>";
		foreach($entities as $entity) {
			echo "<th>" . htmlspecialchars($entity->title()) . "</th>";
		}
		echo "</tr></thead><tbody>\n";
		// user results
		foreach ($users as $userinfo) {
			$subms = $userinfo['subms'];
			$user  = $userinfo['user'];
			echo "<tr>";
			// username
			echo '<td><a href="admin_user.php?edit='.urlencode($user->userid).'">' . htmlspecialchars($user->name_and_login()) . '</a></td>';
			// submissions
			foreach($entities as $e => $entity) {
				$subm = isset($subms[$e]) ? $subms[$e] : false;
				$status = Status::to_status($subm);
				echo '<td class="'.Status::to_css_class($status).'">'.Status::to_short_text($status).'</td>';
			}
			echo "</tr>\n";
		}
		echo "</tbody></table>\n";
	}
	
}

$page = new Page();
$page->write();
