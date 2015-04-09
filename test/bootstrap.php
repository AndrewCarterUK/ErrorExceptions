<?php

use ErrorExceptions\ErrorExceptions;

require_once dirname(__DIR__) . '/vendor/autoload.php';

error_reporting(E_ALL | E_DEPRECATED);
$handler = new ErrorExceptions(E_ALL | E_DEPRECATED);
$handler->register();
