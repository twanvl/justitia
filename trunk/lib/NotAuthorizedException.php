<?php

// -----------------------------------------------------------------------------
// Exceptions indicating that the user is not authorized to view a page
// -----------------------------------------------------------------------------

class NotAuthorizedException extends Exception {
	function __construct() {
		Exception::__construct("Administrators only");
	}
	function getDetails() {
		return "This area is only acccessible to users with administrator priviliges.";
	}
}
