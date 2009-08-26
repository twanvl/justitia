<?php

// where are courses located?
define('COURSE_DIR',       '../courses');
define('SUBMISSION_DIR',   '../submissions');
define('PENDING_DIR',      '../submissions/pending');
define('TEMP_JUDGING_DIR', ''); // empty string for system wide temp dir

// what time are we in?
define('TIMEZONE', 'Europe/Paris');

// how long before submissions are re-judged if no answer has yet been received (in seconds)?
//define('REJUDGE_TIMEOUT', 120);
define('REJUDGE_TIMEOUT', 1);//Debug

// the database connection
define('DB_PATH',     'mysql:host=localhost;dbname=new_athena');
define('DB_USERNAME', 'new_athena');
define('DB_PASSWORD', 'Klqonhtak7');
define('DB_PERSISTENT', true);

// default attribute values
$attribute_defaults['allow archives'] = false;
$attribute_defaults['submitable']     = false;
$attribute_defaults['compile']        = true;
$attribute_defaults['filename regex'] = '';
$attribute_defaults['visible']        = true;
$attribute_defaults['show date']      = 'always';
$attribute_defaults['hide date']      = 'never';
$attribute_defaults['start date']     = 'always';
$attribute_defaults['end date']       = 'never';
$attribute_defaults['timelimit']      = 1;
$attribute_defaults['timelimit']      = 100000;
$attribute_defaults['keep best']      = true;

