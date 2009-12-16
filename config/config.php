<?php

// -----------------------------------------------------------------------------
// Directories
// -----------------------------------------------------------------------------

// Course data files
define('COURSE_DIR',       '../courses');

// Program to safely run submissions
define('RUNGUARD_PATH',    'bin/runguard');
define('RUNGUARD_USER',    'corrector');

// -----------------------------------------------------------------------------
// Database
// -----------------------------------------------------------------------------

// the database connection
define('DB_PATH',     'mysql:host=localhost;dbname=new_athena');
define('DB_USERNAME', 'new_athena');
define('DB_PASSWORD', 'Klqonhtak7');
define('DB_PERSISTENT', true);

// -----------------------------------------------------------------------------
// Other options
// -----------------------------------------------------------------------------

// how long before submissions are re-judged if no answer has yet been received (in seconds)?
//define('REJUDGE_TIMEOUT', 120);
define('REJUDGE_TIMEOUT', 1);//Debug

// how long should the judge deamon sleep if there are no submissions?
define('DAEMON_SLEEP_TIME', 1);

// -----------------------------------------------------------------------------
// default attribute values
// -----------------------------------------------------------------------------

$attribute_defaults['allow archives']		= false;
$attribute_defaults['submitable']		= false;
$attribute_defaults['compile']			= true;
$attribute_defaults['filename regex']		= '';
$attribute_defaults['visible']			= true;
$attribute_defaults['show date']		= 'always';
$attribute_defaults['hide date']		= 'never';
$attribute_defaults['start date']		= 'always';
$attribute_defaults['end date']			= 'never';
$attribute_defaults['max group size']		= 1000000000;
$attribute_defaults['keep best']		= true;
$attribute_defaults['compile time limit']	= 30;
$attribute_defaults['time limit']		= 1;
$attribute_defaults['memory limit']		= 100000;
$attribute_defaults['filesize limit']		= 0.5*1000*1000; // 0.5 MB
$attribute_defaults['show compile errors']	= true;
$attribute_defaults['show run errors']		= 'all';
$attribute_defaults['show input/output']	= 'none';
$attribute_defaults['compiler']			= ''; // automatic
$attribute_defaults['runner']			= 'run';
$attribute_defaults['checker']			= 'diff';
