<?php

// -----------------------------------------------------------------------------
// Directories
// -----------------------------------------------------------------------------

// Course data files
define('COURSE_DIR',       '../courses');

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

// what timezone are we in?
define('TIMEZONE', 'Europe/Paris');

// how long before submissions are re-judged if no answer has yet been received (in seconds)?
//define('REJUDGE_TIMEOUT', 120);
define('REJUDGE_TIMEOUT', 1);//Debug

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
$attribute_defaults['max group size']		= 1e100;
$attribute_defaults['keep best']		= true;
$attribute_defaults['time limit']		= 1;
$attribute_defaults['memory limit']		= 100000;
//$attribute_defaults['output limit']		= 2*1000*1000; // mysql doesn't allow larger files
$attribute_defaults['output limit']		= 0.5*1000*1000; // 0.5 MB
$attribute_defaults['show compile errors']	= true;
$attribute_defaults['show run errors']		= 'all';
$attribute_defaults['show input/output']	= 'none';
$attribute_defaults['compiler']			= ''; // automatic
$attribute_defaults['runner']			= 'run';
$attribute_defaults['checker']			= 'diff';
