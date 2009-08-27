
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
, `is_admin`  boolean         NOT NULL                COMMENT 'Is this an administrator?'
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
, `file_path`    varchar(255)    NOT NULL                COMMENT 'Path to the submitted file, points to a directory'
, `file_name`    varchar(255)    NOT NULL                COMMENT 'Original name of the submited file'
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


/* -----------------------------------------------------------------------------
 * Initial user
 *  Username: admin
 *  Password: password
 * ----------------------------------------------------------------------------- */

INSERT INTO `user` (login,firstname,midname,lastname,password,is_admin)
            VALUES ('admin','Change','','Immediately','d25c1159def4b96f986e26ef5ac91249d1575c7bwllH0Y}vR1',1);
