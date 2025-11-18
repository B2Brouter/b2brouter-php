<?php
/**
 * Bootstrap file for B2BRouter PHP SDK examples
 *
 * This file loads environment variables from .env and provides helper functions
 * for examples. All example files should include this bootstrap.
 *
 * Setup:
 *   1. Copy .env.example to .env: cp .env.example .env
 *   2. Edit .env and add your B2BRouter API credentials
 *   3. Run any example: php examples/your_example.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables from .env file
$dotenvPath = dirname(__DIR__);
if (class_exists('\Dotenv\Dotenv') && file_exists($dotenvPath . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable($dotenvPath);
    $dotenv->safeLoad(); // safeLoad won't throw if .env doesn't exist
}

/**
 * Get environment variable with fallback.
 *
 * Checks $_ENV, $_SERVER, and getenv() in that order.
 *
 * @param string $key Environment variable name
 * @param mixed $default Default value if not found
 * @return mixed
 */
function env($key, $default = null)
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    return $value !== false ? $value : $default;
}

/**
 * Check if required environment variables are set.
 *
 * Exits with error message if any required variables are missing.
 *
 * @param array $required Array of required environment variable names
 * @return void
 */
function checkRequiredEnv(array $required = ['B2B_API_KEY', 'B2B_ACCOUNT_ID'])
{
    $missing = [];

    foreach ($required as $key) {
        if (!env($key)) {
            $missing[] = $key;
        }
    }

    if (!empty($missing)) {
        echo "\n";
        echo "========================================\n";
        echo "ERROR: Missing Required Configuration\n";
        echo "========================================\n\n";
        echo "The following environment variables are required:\n";
        foreach ($missing as $key) {
            echo "  ✗ $key\n";
        }
        echo "\nSetup Instructions:\n";
        echo "  1. Copy .env.example to .env:\n";
        echo "     cp .env.example .env\n\n";
        echo "  2. Edit .env and add your B2BRouter credentials\n\n";
        echo "  3. Get your credentials from:\n";
        echo "     https://app.b2brouter.net\n\n";
        exit(1);
    }
}

/**
 * Display example header.
 *
 * @param string $title Example title
 * @param string $description Optional description
 * @return void
 */
function exampleHeader($title, $description = '')
{
    echo "\n";
    echo str_repeat('=', 60) . "\n";
    echo $title . "\n";
    echo str_repeat('=', 60) . "\n";
    if ($description) {
        echo $description . "\n";
        echo str_repeat('-', 60) . "\n";
    }
    echo "\n";
}
