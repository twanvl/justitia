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

function ldap_connect_and_login($login,$password) {
	$con = ldap_connect(LDAP_SERVER);
	if (!$con) return false;
	ldap_set_option($con, LDAP_OPT_PROTOCOL_VERSION, 3);
	$bind = @ldap_bind($con,ldap_dn_from_login($login), $password);
	if (!$bind) return false;
	return $con;
}

function starts_upper($x) {
	return !empty($x) && strtoupper($x[0]) === $x[0];
}

// Split a name like "T.M. van Laarhoven" into ("T.M.","van","Laarhoven")
function split_name($fullname) {
	$words = explode(' ',$fullname);
	if (count($words) < 2) return array('','',$fullname);
	$first = array_shift($words);
	// Tussenvoegsels beginnen met een kleine letter
	$mid = array();
	while (!empty($words) && !starts_upper($words[0])) {
	    $mid []= array_shift($words);
	}
	// er is altijd een achternaam
	if (empty($words)) {
	   $words = $mid; $mid = array();
	}
	$name = array($first,implode(' ',$mid),implode(' ',$words));
	return $name;
}

function userdata_from_ldap($entry) {
	if (!isset($entry['lwpfullname'],$entry['lwpmailaddress'])) return false;
	$name = $entry['lwpfullname'][0];
	$email = $entry['lwpmailaddress'][0];
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
