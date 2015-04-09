<?php

use ErrorExceptions\ErrorExceptions;

require_once 'vendor/autoload.php';

error_reporting(E_ALL | E_DEPRECATED);
$handler = new ErrorExceptions(E_ALL | E_DEPRECATED);
$handler->register();

try {
    strpos('test', 'bar', 10);
} catch (Exception $e) {
    // Not executed, since it's an incidental error
    var_dump(get_class($e));
}

try {
    fopen('bar.baz.biz', 'r');
} catch (Exception $e) {
    // Caught it!
    var_dump(get_class($e));
}
