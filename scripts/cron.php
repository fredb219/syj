#!/usr/bin/env php
<?php

require_once realpath(dirname(__FILE__) . '/../public/init.php');

$application->bootstrap();

foreach (glob(realpath(APPLICATION_PATH . '/crons') . '/*.php') as $fname) {
    require_once ($fname);
}
