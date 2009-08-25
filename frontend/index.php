<?php

require_once('./bootstrap.inc');
require_once('./template.inc');

class Page extends Template {
function title() {
	return "Welcome";
}
function write_body() {

$user = Authentication::require_user();
echo "Hello " . $user->name();


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
// Directory listing
// -----------------------------------------------------------------------------

//require_once('template.inc');

function write_tree($e) {
	echo "<ul>";
	echo "<pre>"; print_r($e->attributes()); echo "</pre>";
	echo "<pre>"; print_r(Authentication::current_user()->submissions_to($e)); echo "</pre>";
	foreach($e->children() as $n => $d) {
		echo "<li><a href='index.php". $d->path() ."'>" . htmlspecialchars($d->attribute("title")) .  "</a>";
		echo $d->visible()    ? 'V+ ' : 'V- ' ;
		echo $d->active() ? 'A+ ' : 'A- ' ;
		//echo $d->submitable() ? 'S+ ' : 'S- ' ;
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


$u = new User('admin');
print_r($u);

echo $u->check_password('password');
*/

// -----------------------------------------------------------------------------
// Submission stuff
// -----------------------------------------------------------------------------

print_r($user->all_submissions());

}}new Page();
