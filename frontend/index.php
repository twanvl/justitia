<?php

require_once('./bootstrap.inc');
require_once('./submit.inc');

// -----------------------------------------------------------------------------
// Main 'entity' page
// -----------------------------------------------------------------------------

class Page extends Template {
	private $entity;
	
	function __construct() {
		// find active entity
		Authentication::require_user();
		$this->entity = Entity::get(@$_SERVER['PATH_INFO']);
		handle_uploaded_submission($this->entity);
	}
	
	function title() {
		return $this->entity->title();
	}
	
	// Navigation
	function write_nav_children($e) {
		if (count($e->children()) == 0) return;
		echo "<li><ul>";
		foreach ($e->children() as $e) {
			if (!$e->visible()) continue;
			
			$class = '';
			if ($e->is_ancestor_of($this->entity)) {
				$class .= 'ancestor ';
			}
			if ($e->attribute_bool('submitable')) {
				$subm = Authentication::current_user()->last_submission_to($e);
				$class .= Status::to_css_class($subm) . ' ';
			}
			if (!$e->active()) {
				$class .= 'inactive ';
			}
			
			echo "<li>";
			echo '<a href="index.php' . $e->path() .'"'
				. ($class ? ' class="'.$class.'"' : '')
				. '>'
				. htmlspecialchars($e->title()) . '</a>';
			echo "</li>";
		}
		echo "</ul></li>";
	}
	function write_nav() {
		echo '<ul id="nav">';
		foreach ($this->entity->ancestors() as $e) {
			$this->write_nav_children($e);
		}
		echo '</ul>';
	}
	
	
	function write_submit_form() {
		$this->write_messages('submit');
?><form action="index.php<?php echo $this->entity->path(); ?>" method="post" enctype="multipart/form-data">
  <label>Select file</label> <input type="file" name="file" id="file"><br>
  <input type="submit" name="submit" value="Submit" id="submit">
</form>
<script type="text/javascript">
<!--
  var file_control = document.getElementById('file');
  file_control.onchange = function() {
	var ok = file_control.value.match(/<?php echo ".*\\.(java|c)"; ?>/);
	document.getElementById('submit').style.backgroundColor = ok ? 'white' : 'red';
  }
//-->
</script><?php
	}


function write_body() {

$user = Authentication::require_user();

$ignore= <<<EOF

<ul>
 <li>Home
 <li>User settings
 <li>Course X
   <ul>
     <li>Quick overview
     <li>Problem X
      <ul>
        <li>Submit
        <li>Submission X details
      </ul>
   </ul>
</ul>

Admin interface
<ul>
 <li>Config overview
 <li>Users
   <ul>
     <li>List
     <li>Find
     <li>Add user(s)
   </ul>
 <li>Courses
   <ul>
     <li>Course X
      <ul>
        <li>Rescan (when problem set has changed)
        <li>Users overview
        <li>Submissions overview
        <li>Problem X
         <ul>
           <li>Users overview
           <li>Submissions overview
            <ul>
              <li>Submission X details
            </ul>
         </ul>
      </ul>
   </ul>
</ul>


EOF;



// -----------------------------------------------------------------------------
// Directory listing
// -----------------------------------------------------------------------------

//require_once('template.inc');

function write_tree($e) {
	echo "<ul>";
	echo "<pre>"; print_r($e->attributes()); echo "</pre>";
	echo "<pre>"; print_r(Authentication::current_user()->submissions_to($e)); echo "</pre>";
	foreach($e->children() as $n => $d) {
		echo "<li><a href='index.php". $d->path() ."'>" . htmlspecialchars($d->title()) .  "</a>";
		echo $d->visible()    ? 'V+ ' : 'V- ' ;
		echo $d->active() ? 'A+ ' : 'A- ' ;
		//echo $d->submitable() ? 'S+ ' : 'S- ' ;
		write_tree($d);
		
		echo "</li>";
	}
	echo "</ul>";
}

//write_tree(Entity::get_root());
//write_tree(Entity::get(""));


function write_nav($here) {
	echo "<ul>";
	foreach ($here->ancestors() as $e) {
		echo "<li>";
		echo '<a href="index.php' . $e->path() .'">' . htmlspecialchars($e->title()) . '</a>';
		echo "</li>";
	}
	echo "</ul>";
}


$here = Entity::get(@$_SERVER['PATH_INFO']);

function format_bool($b) {
	return $b ? "yes" : "no";
}
function write_submitable_entity_info($entity) {
	echo "<table>";
	echo "<tr><td>Can submit</td><td>" . format_bool($entity->active()) . "</td>";
	echo "<tr><td>Deadline</td><td>"   . format_date($entity->active_range()->end) . "</td>";
	echo "<tr><td>Language</td><td>"   . ($entity->attribute('language')) . "</td>";
	echo "<tr><td>Archives allowed</td><td>" . format_bool($entity->attribute_bool('allow archives')) . "</td>";
	echo "</table>";
}


function write_submission($i, $subm) {
	$path = "download.php/" . $subm->submissionid . '/' . $subm->file_name;
	echo '<div class="ordinal">'.$i.'</div>';
	echo "<table>";
	echo "<tr><td>Submitted on</td><td>" . format_date($subm->time) . "</td>";
	echo "<tr><td>Submitted by</td><td>" . User::names_html($subm->users()) . "</td>";
	echo '<tr><td>Files</td><td><a href="'.$path.'">Download submitted files</a></td>';
	echo "<tr><td>Status</td><td>"       . Status::to_text($subm) . "</td>";
	echo "</table>";
}

if ($here->attribute_bool('submitable')) {
	write_submitable_entity_info($here);
	
	// submission form
	echo "<h2>Submit</h2>";
	$this->write_submit_form();
	
	echo "<h2>Submissions</h2>";
	$submissions = Authentication::current_user()->submissions_to($here);
	if (empty($submissions)) {
		echo "<em>no submissions have been made for this assignment.</em>";
	} else {
		echo '<ul class="submissions">';
		$i = count($submissions);
		foreach($submissions as $subm) {
			echo '<li class="submission '.Status::to_css_class($subm).'">';
			write_submission($i, $subm);
			$i--;
			echo '</li>';
		}
		echo '</ul>';
	}
	
}

// -----------------------------------------------------------------------------
// Authentication stuff
// -----------------------------------------------------------------------------

/*new Authentication();

$h = make_salted_password_hash("password");
echo $h;

echo "<br>",check_salted_password_hash("password","44d762454ab6ceebfcca586edc3342d7f9eb6c443yVtEjzEbs");

echo $u->check_password('password');
*/

// -----------------------------------------------------------------------------
// Submission stuff
// -----------------------------------------------------------------------------

//print_r($user->all_submissions());

}}

$page = new Page();
$page->write();
