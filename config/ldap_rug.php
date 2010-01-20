<?php

// -----------------------------------------------------------------------------
// LDAP authentication for the University of Groningen (rug)
// -----------------------------------------------------------------------------


define('LDAP_SERVER',  "ldaps://lwap.service.rug.nl");
define('LDAP_BASE_DN', "ou=lwp,o=rug,c=nl");

function ldap_dn_from_login($login) {
	if ($login{0} == 's') {
		return "cn=$login,ou=student,ou=lwp,o=rug,c=nl";
	} else if ($login{0} == 'p') {
		return "cn=$login,ou=staff,ou=lwp,o=rug,c=nl";
	} else {
		return "cn=$login," . LDAP_BASE_DN;
	}
}

function ldap_connect_and_login($login,$pass) {
	$con = ldap_connect(LDAP_SERVER);
	if (!$con) return false;
	ldap_set_option($con, LDAP_OPT_PROTOCOL_VERSION, 3);
	$bind = @ldap_bind($con,ldap_dn_from_login($login), $password);
	if (!$bind) return false;
	return $con;
}

function split_name($fullname) {
	$words = explode(' ',$fullname);
	// TODO
	//$firstname = $words[0];
	//return array($firstname,$midname,$lastname);
	return array('','',$fullname);
}

function userdata_from_ldap($entry) {
	$name = $entry['lwpfullname'];
	$email = $entry['lwpmailaddress'];
	$class = strpos($entry['dn'],'student')!==false ? 'student' : 'staff';
	list($firstname,$midname,$lastname) = split_name($name);
	return array(
		'firstname' => $firstname,
		'midname'   => $midname,
		'lastname'  => $lastname,
		'email'     => $email,
		'class'     => $class,
		'notes'     => ''
	);
}
