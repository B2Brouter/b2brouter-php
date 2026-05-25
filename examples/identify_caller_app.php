<?php
/**
 * Example: Identifying the integrating application in User-Agent
 *
 * The SDK sends a `User-Agent` header on every request, identifying itself,
 * the PHP runtime, and libcurl. Applications that wrap the SDK (integrators,
 * plugins, custom tools) can identify themselves by passing `app_info`.
 *
 * This helps B2BRouter support correlate requests with the SDK version and
 * the integrating application when investigating issues.
 *
 * This example runs entirely client-side — it does not require an API key
 * or make any network calls. It only prints the `User-Agent` the SDK would
 * send under different configurations.
 */

require_once __DIR__ . '/bootstrap.php';

use B2BRouter\B2BRouterClient;

exampleHeader(
    'User-Agent: SDK self-identification only',
    'No app_info passed. The SDK identifies itself, PHP, and libcurl.'
);

$defaultClient = new B2BRouterClient('sk_dummy_key');
echo "User-Agent: {$defaultClient->getUserAgent()}\n";

exampleHeader(
    'User-Agent: with caller app (name only)',
    'Minimum app_info: just a name.'
);

$nameOnlyClient = new B2BRouterClient('sk_dummy_key', [
    'app_info' => [
        'name' => 'B2BRouter-WooCommerce',
    ],
]);
echo "User-Agent: {$nameOnlyClient->getUserAgent()}\n";

exampleHeader(
    'User-Agent: with caller app (full)',
    'Full app_info: name, version, and URL.'
);

$fullClient = new B2BRouterClient('sk_dummy_key', [
    'app_info' => [
        'name'    => 'B2BRouter-WooCommerce',
        'version' => '1.0.3',
        'url'     => 'https://shop.example.com',
    ],
]);
echo "User-Agent: {$fullClient->getUserAgent()}\n";

echo "\n";
echo "SDK version constant: " . B2BRouterClient::VERSION . "\n";
