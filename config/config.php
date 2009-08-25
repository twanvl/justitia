<?php

// where are courses located?
define('COURSE_DIR',  '../courses');
define('PENDING_DIR', '../submissions/pending');

// what time are we in?
define('TIMEZONE', 'Europe/Paris');

// how long before submissions are re-judged if no answer has yet been received (in seconds)?
define('REJUDGE_TIMEOUT', 120);

// the database connection
define('DB_PATH',     'mysql:host=localhost;dbname=new_athena');
define('DB_USERNAME', 'new_athena');
define('DB_PASSWORD', 'Klqonhtak7');
define('DB_PERSISTENT', true);

