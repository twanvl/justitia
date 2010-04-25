<?php

// -----------------------------------------------------------------------------
// Internal errors
// these are not the users fault, but they mean that the system administrators have to fix something
// -----------------------------------------------------------------------------

class InternalException extends Exception {
	function __construct($msg) {
		Exception::__construct($msg);
	}
}
