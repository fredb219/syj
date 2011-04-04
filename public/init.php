<?php

// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(__DIR__ . '/../application'));

// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

// set include directories to '.' and ../library
set_include_path('.' . PATH_SEPARATOR . realpath(APPLICATION_PATH . '/../library'));

/** Zend_Application */
require_once 'Zend/Application.php';

# we use this function as a marker so xgettext knows it must extract this
# string. This function can be used when string must be translated, but not at
# the place it's called. For example, Zend_Form uses a translator to translate
# string it has been given. So, we must pass it a non translated string.
function __($str) {
    return $str;
}

$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.ini'
);
