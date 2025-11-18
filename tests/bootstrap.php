<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Ensure we're in test mode
define('B2B_TEST_MODE', true);
