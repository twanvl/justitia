
/*
 * Set up the database
 */


/*
 * Users
 */
CREATE TABLE `user`
( `userid`    int(4) unsigned NOT NULL auto_increment COMMENT 'Unique ID'
, `login`     varchar(255) NOT NULL COMMENT 'Official student number, used as login name'
, `firstname` varchar(255) NOT NULL COMMENT 'First name(s)'
, `midname`   varchar(20)  NOT NULL COMMENT 'Tussenvoegsels'
, `lastname`  varchar(255) NOT NULL COMMENT 'Last name'
, `password`  varchar(255) NOT NULL COMMENT 'SHA1 hash of the salted password'
, `is_admin`  boolean      NOT NULL COMMENT 'Is this an administrator?'
, PRIMARY KEY(`userid`)
, INDEX      (`login`)
) DEFAULT CHARSET=utf8;

/*
 * Submissions
 */
CREATE TABLE `submission`
( `submissionid` int(4) unsigned NOT NULL auto_increment COMMENT 'Unique ID'
, `time`         int(8)       NOT NULL COMMENT 'Date/Time of submission'
, `entity_path`  varchar(255) NOT NULL COMMENT 'Path to the problem'
, `file_path`    varchar(255) NOT NULL COMMENT 'Path to the submitted file, points to a directory'
, `file_name`    varchar(255) NOT NULL COMMENT 'Original name of the submited file'
, `judge_host`   varchar(255) default NULL COMMENT 'Name of the host that judged this submission'
, `judge_start`  int(8)       NOT NULL COMMENT 'Date/Time of start of judging, or 0 if not judging yet'
, `status`       int(4)       NOT NULL COMMENT 'Status code: 0=fail, 1=success, 2=pending'
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
 * Entities <-> users -> status
 */
CREATE TABLE `user_entity_status`
( `userid`       int(4) unsigned NOT NULL
, `entity_path`  varchar(255) NOT NULL COMMENT 'Path to the entity'
, `valid_before` int(8)       NOT NULL COMMENT 'Date/Time when new children become active'
, `status`       int(4) NOT NULL COMMENT 'Status code: 0=fail, 1=success, 2=pending, 3=missing'
) DEFAULT CHARSET=utf8;


CREATE TABLE `pending`
( `submissionid` int(4)       NOT NULL COMMENT 'Team that made the submission'
, `problempath` varchar(255) NOT NULL COMMENT 'Path to the problem'
, `filepath`    varchar(255) NOT NULL COMMENT 'Path to the submitted file(s)'
, `judgedeamon` varchar(255) default NULL COMMENT 'Judge that is checking this submission, or null if none'
) DEFAULT CHARSET=utf8;

CREATE TABLE `team`
( `teamid`
, `userid`
) DEFAULT CHARSET=utf8;

/*
CREATE TABLE `participation`
( `userid`
, `coursepath` varchar(255) NOT NULL COMMENT 'Path to the course this team participates in'
) DEFAULT CHARSET=utf8;
*/

CREATE TABLE `judgedeamon`
( `hostname` varchar(255) NOT NULL COMMENT 'Hostname this judge is running on'
, `active`   tinyint(1) unsigned NOT NULL default '1' COMMENT 'Should this host take on judgings?'
, `pingtime` datetime     NOT NULL COMMENT 'Last ping from this host'
) DEFAULT CHARSET=utf8;
