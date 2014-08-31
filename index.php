<?php

include(__DIR__ . '/webdata/init.inc.php');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

if (function_exists('setproctitle')) {
    setproctitle('WMS: ' . $_SERVER['REQUEST_URI']);
}

Pix_Controller::addCommonHelpers();
Pix_Controller::dispatch(__DIR__ . '/webdata/');

if (function_exists('setproctitle')) {
    setproctitle('php-fpm');
}
