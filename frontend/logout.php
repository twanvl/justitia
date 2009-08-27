<?php

require_once('../lib/bootstrap.inc');

Authentication::logout();
Util::redirect(@$_REQUEST['redirect']);
