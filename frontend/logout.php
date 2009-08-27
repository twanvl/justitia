<?php

require_once('./bootstrap.inc');

Authentication::logout();
Util::redirect(@$_REQUEST['redirect']);
