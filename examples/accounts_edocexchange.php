<?php
/**
 * Example: Account management (eDocExchange)
 *
 * Demonstrates account operations available to eDocExchange subscriptions:
 * list, retrieve, update, upload/delete logo, and delete.
 *
 * eDocExchange subscriptions cannot create accounts via API — accounts
 * must be added through the B2BRouter web application.
 *
 * Usage:
 *   php examples/accounts_edocexchange.php              # List accounts only
 *   php examples/accounts_edocexchange.php <account_id>  # Full flow for a specific account
 */

require_once __DIR__ . '/bootstrap.php';

use B2BRouter\B2BRouterClient;
use B2BRouter\Exception\ApiErrorException;

checkRequiredEnv(['B2B_API_KEY']);

$client = new B2BRouterClient(env('B2B_API_KEY'), [
    'api_version' => env('B2B_API_VERSION', '2026-03-02'),
    'api_base' => env('B2B_API_BASE'),
]);

$accountId = isset($argv[1]) ? $argv[1] : null;

// ============================================================
// 1. List accounts
// ============================================================

exampleHeader('1. List Accounts');

try {
    $accounts = $client->accounts->all(['limit' => 10]);

    echo "Found {$accounts->count()} accounts";
    if ($accounts->getTotal()) {
        echo " (Total: {$accounts->getTotal()})";
    }
    echo "\n\n";

    foreach ($accounts as $account) {
        printf("  ID: %-10s Name: %-30s TIN: %s\n",
            $account['id'],
            $account['name'] ?? 'N/A',
            $account['tin_value'] ?? 'N/A'
        );
    }
} catch (ApiErrorException $e) {
    echo "Error listing accounts: {$e->getMessage()}\n";
}

if (!$accountId) {
    echo "\nTo run the full flow, pass an account ID:\n";
    echo "  php examples/accounts_edocexchange.php <account_id>\n\n";
    exit(0);
}

// ============================================================
// 2. Retrieve account
// ============================================================

exampleHeader('2. Retrieve Account');

try {
    $account = $client->accounts->retrieve($accountId);

    echo "Account details:\n";
    echo "  ID:       {$account['id']}\n";
    echo "  Name:     {$account['name']}\n";
    echo "  TIN:      " . ($account['tin_value'] ?? 'N/A') . "\n";
    echo "  Country:  " . ($account['country'] ?? 'N/A') . "\n";
    echo "  Email:    " . ($account['email'] ?? 'N/A') . "\n";
    echo "  Has Logo: " . ($account['has_logo'] ? 'Yes' : 'No') . "\n";

    // Save original name to restore later
    $originalName = $account['name'];

    // ============================================================
    // 3. Update account
    // ============================================================

    exampleHeader('3. Update Account');

    $updated = $client->accounts->update($accountId, [
        'account' => ['name' => $originalName . ' (SDK test)']
    ]);
    echo "Updated name: {$updated['name']}\n";

    // Restore original name
    $client->accounts->update($accountId, [
        'account' => ['name' => $originalName]
    ]);
    echo "Restored name: {$originalName}\n";

    // ============================================================
    // 4. Upload a logo
    // ============================================================

    exampleHeader('4. Upload Logo');

    $logoData = file_get_contents(__DIR__ . '/logo.png');
    $withLogo = $client->accounts->uploadLogo($accountId, $logoData);
    echo "Has logo: " . ($withLogo['has_logo'] ? 'yes' : 'no') . "\n";

    // ============================================================
    // 5. Delete the logo
    // ============================================================

    exampleHeader('5. Delete Logo');

    $withoutLogo = $client->accounts->deleteLogo($accountId);
    echo "Has logo: " . ($withoutLogo['has_logo'] ? 'yes' : 'no') . "\n";

    // ============================================================
    // 6. Delete (archive) account
    // ============================================================

    exampleHeader('6. Delete Account');

    $deleted = $client->accounts->delete($accountId);
    echo "Deleted account: {$deleted['id']}\n";

} catch (ApiErrorException $e) {
    echo "Error: {$e->getMessage()}\n";
    if ($e->getRequestId()) {
        echo "Request ID: {$e->getRequestId()}\n";
    }
}
