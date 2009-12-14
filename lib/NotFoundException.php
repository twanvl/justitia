<?php

// -----------------------------------------------------------------------------
// Exceptions resulting in a not found (http 404) error page
// -----------------------------------------------------------------------------

class NotFoundException extends Exception {
	function __construct($msg) {
		Exception::__construct($msg);
	}
}
