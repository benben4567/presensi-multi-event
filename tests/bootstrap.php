<?php

// Force testing environment before the app loads, bypassing any cached config.
putenv('APP_ENV=testing');
$_ENV['APP_ENV'] = 'testing';
$_SERVER['APP_ENV'] = 'testing';

// Point cache paths to non-existent files so the test app rebuilds config/routes
// from source, ensuring APP_ENV=testing is respected even if config:cache was run.
putenv('APP_CONFIG_CACHE=/tmp/laravel_test_config.php');
putenv('APP_ROUTES_CACHE=/tmp/laravel_test_routes_v7.php');

require __DIR__.'/../vendor/autoload.php';
