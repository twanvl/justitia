<?php
require_once('../lib/bootstrap.inc');

/**
 * This is a simple inefficient script that repopulates the 'users_entity' table
 */

DB::prepare_query($t, "TRUNCATE `user_entity`");
$t->execute();

DB::prepare_query($query, "SELECT * FROM user");
$query->execute();
$query->setFetchMode(PDO::FETCH_ASSOC);
$users = $query->fetchAll();
$num_users = count($users);
$i = 1;
foreach($users as $user) {
	if($i % 10 == 0 || $i == $num_users) {
		print("At user $i of $num_users (".intval($i / $num_users * 100)."%)\n");
	}
	
	// get all entities
	DB::prepare_query($query2, "SELECT DISTINCT s.entity_path FROM submission as s JOIN user_submission as us ON s.submissionid = us.submissionid WHERE us.userid = ?");
	$query2->execute(array($user['userid']));
	$query2->setFetchMode(PDO::FETCH_ASSOC);
	foreach($query2->fetchAll() as $entity_path) {
//		print("user ".$user['userid']." path ".$entity_path['entity_path']."\n");
		DB::prepare_query($qbest, "SELECT * FROM submission AS s JOIN user_submission AS us ON s.submissionid = us.submissionid WHERE us.userid = ? AND s.entity_path = ? ORDER BY `s`.`status` DESC, `s`.`submissionid` DESC LIMIT 1");
		$qbest->execute(array($user['userid'], $entity_path['entity_path']));
		$best = $qbest->fetch();
		DB::prepare_query($qlast, "SELECT * FROM submission AS s JOIN user_submission AS us ON s.submissionid = us.submissionid WHERE us.userid = ? AND s.entity_path = ? ORDER BY `s`.`submissionid` DESC LIMIT 1");
		$qlast->execute(array($user['userid'], $entity_path['entity_path']));
		$last = $qlast->fetch();
//		print("Best: ".$best['submissionid']." last: ".$last['submissionid']."\n");
		DB::prepare_query($insert, "INSERT INTO `user_entity` (`userid`, `entity_path`, `last_submissionid`, `best_submissionid`) VALUES (?, ?, ?, ?)");
		$insert->execute(array($user['userid'], $entity_path['entity_path'], $last['submissionid'], $best['submissionid']));
	}
	
	$i++;
}