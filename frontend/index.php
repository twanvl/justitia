<?php

require_once('./bootstrap.inc');
require_once('./template.inc');

$page = new OutputPage();
$page->title = "Welcome";
$page->show();


echo "Welcome to the Apollo programming assigment verification system";

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
// Ranges of dates/times
// -----------------------------------------------------------------------------

function parse_date($date_str, $rel=NULL) {
	if (is_int($date_str))     return $date_str; // was already a timestamp
	if ($date_str == 'always') return 0;
	if ($date_str == 'never')  return (float)'INF';
	else                       return strtotime($date_str, $rel);
}

class DateRange {
	// start/end timestamps
	var $start;
	var $end;
	
	function __construct($start_str, $end_str) {
		$this->start = parse_date($start_str);
		$this->end   = parse_date($end_str);
	}
	
	// Does this range contain the given time?
	function contains($date) {
		return $this->start <= $date && $date < $this->end;
	}
	
	// Does this range contain the current time?
	function contains_now() {
		return $this->contains(now());
	}
}


// -----------------------------------------------------------------------------
// Directory listing
// -----------------------------------------------------------------------------

//require_once('template.inc');

function write_tree($e) {
	echo "<ul>";
	echo "<pre>"; print_r($e->attributes()); echo "</pre>";
	foreach($e->children() as $n => $d) {
		echo "<li><a href='". $d->path() ."'>" . htmlspecialchars($d->attribute("title")) .  "</a>";
		write_tree($d);
		
		echo "</li>";
	}
	echo "</ul>";
}

//write_tree(Entity::get_root());
write_tree(Entity::get(""));

new Testset(Entity::get("impprog0910/week1/welkom"));

// -----------------------------------------------------------------------------
// Authentication stuff
// -----------------------------------------------------------------------------

/*new Authentication();

$h = make_salted_password_hash("password");
echo $h;

echo "<br>",check_salted_password_hash("password","44d762454ab6ceebfcca586edc3342d7f9eb6c443yVtEjzEbs");
*/

$u = new User('admin');
print_r($u);

echo $u->check_password('password');
