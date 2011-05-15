
/* -----------------------------------------------------------------------------
 * Set up the database
 * ----------------------------------------------------------------------------- */

/*
 * Users
 */
CREATE TABLE `user`
( `userid`    int(4) unsigned NOT NULL auto_increment COMMENT 'Unique ID'
, `login`     varchar(255)    NOT NULL                COMMENT 'Official student number, used as login name'
, `firstname` varchar(255)    NOT NULL                COMMENT 'First name(s)'
, `midname`   varchar(20)     NOT NULL                COMMENT 'Tussenvoegsels'
, `lastname`  varchar(255)    NOT NULL                COMMENT 'Last name'
, `password`  varchar(255)    NOT NULL                COMMENT 'SHA1 hash of the salted password'
, `auth_method` char(4)       NOT NULL default "pass" COMMENT 'Authentication method, possible values: pass, ldap '
, `is_admin`  boolean         NOT NULL                COMMENT 'Is this an administrator?'
, `class`     varchar(255)    NOT NULL                COMMENT 'Study direction / class'
, `email`     varchar(255)    NOT NULL                COMMENT 'Email address'
, `notes`     mediumtext      NOT NULL                COMMENT 'Comments and/or notes'
, PRIMARY KEY(`userid`)
, INDEX      (`login`)
) DEFAULT CHARSET=utf8;

/*
 * Submissions
 */
CREATE TABLE `submission`
( `submissionid` int(4) unsigned NOT NULL auto_increment COMMENT 'Unique ID'
, `time`         int(8)          NOT NULL                COMMENT 'Date/Time of submission'
, `entity_path`  varchar(255)    NOT NULL                COMMENT 'Path to the problem'
, `judge_host`   varchar(255)    default NULL            COMMENT 'Name of the host that judged this submission'
, `judge_start`  int(8)          NOT NULL                COMMENT 'Date/Time of start of judging, or 0 if not judging yet'
, `status`       int(4)          NOT NULL                COMMENT 'Status code: (See lib/Status.php)'
, PRIMARY KEY(`submissionid`)
, INDEX(`status`) /* for finding judgable submissions */
) DEFAULT CHARSET=utf8;

/*
 * Submissions <-> users:
 *  user X made submission Y
 */
CREATE TABLE `user_submission`
( `userid`       int(4) unsigned NOT NULL
, `submissionid` int(4) unsigned NOT NULL
, PRIMARY KEY(`userid`,`submissionid`)
) DEFAULT CHARSET=utf8;

/*
 * Submited/output files
 */
CREATE TABLE `file`
( `submissionid` int(4) unsigned NOT NULL auto_increment COMMENT 'The submission to which this file belongs'
, `filename`     varchar(255)    NOT NULL                COMMENT 'Name of the file: either code/.. or out/..'
, `data`         mediumblob                              COMMENT 'File contents'
, PRIMARY KEY(`submissionid`,`filename`)
) DEFAULT CHARSET=utf8;

/*
 * Judge daemons
 */
CREATE TABLE `judge_daemon`
( `judgeid`      int(4) unsigned NOT NULL auto_increment COMMENT 'Unique ID'
, `judge_host`   varchar(255)    NOT NULL                COMMENT 'More user friendly unique name'
, `status`       int(4)          NOT NULL                COMMENT 'Status code: (See lib/JudgeDaemon.php)'
, `start_time`   int(8)          NOT NULL                COMMENT 'Date/Time this daemon was started'
, `ping_time`    int(8)          NOT NULL                COMMENT 'Date/Time of last action'
, PRIMARY KEY(`judgeid`)
) DEFAULT CHARSET=utf8;

/*
 * Error log
 */
CREATE TABLE `error_log`
( `logid`        int(4) unsigned NOT NULL auto_increment COMMENT 'Unique ID'
, `judge_host`   varchar(255)                            COMMENT 'Name of the judge, if any'
, `entity_path`  varchar(255)                            COMMENT 'Path to the problem, if any'
, `time`         int(8)          NOT NULL                COMMENT 'Date/Time this message was logged'
, `message`      varchar(255)                            COMMENT 'The message'
, PRIMARY KEY(`logid`)
) DEFAULT CHARSET=utf8;

/*
 * User entity_path lookup table, used for fast results table
 */
CREATE TABLE `user_entity`
( `userid`       int(4) unsigned NOT NULL
, `entity_path`  varchar(255)    NOT NULL                COMMENT 'Path to the problem'
, `last_submissionid` int(4) unsigned NOT NULL
, `best_submissionid` int(4) unsigned NOT NULL
, PRIMARY KEY(`userid`,`entity_path`)
) DEFAULT CHARSET=utf8;


/* -----------------------------------------------------------------------------
 * Initial user
 *  Username: admin
 *  Password: password
 * ----------------------------------------------------------------------------- */

INSERT INTO `user` (login,firstname,midname,lastname,password,is_admin)
            VALUES ('admin','Change','','Immediately','d25c1159def4b96f986e26ef5ac91249d1575c7bwllH0Y}vR1',1);
