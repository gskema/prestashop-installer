<?php

use Symfony\Component\Filesystem\Filesystem;

require(__DIR__.'/../vendor/autoload.php');

define('TESTS_DIR', __DIR__);
define('TESTING_DIR', TESTS_DIR.'/sandbox');

$fs = new Filesystem();
$fs->remove(TESTING_DIR);
