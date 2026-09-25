<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

putenv('APP_ENV=test');
$_ENV['APP_ENV'] = 'test';
$_SERVER['APP_ENV'] = 'test';
