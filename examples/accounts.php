<?php
/**
 * Example: Account management
 *
 * Demonstrates listing, creating, retrieving, updating, and deleting accounts.
 */

require_once __DIR__ . '/bootstrap.php';

use B2BRouter\B2BRouterClient;
use B2BRouter\Exception\ApiErrorException;

checkRequiredEnv();

$client = new B2BRouterClient(env('B2B_API_KEY'), [
    'api_version' => env('B2B_API_VERSION', '2026-03-02'),
    'api_base' => env('B2B_API_BASE'),
]);

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

// ============================================================
// 2. List accounts with query filter
// ============================================================

exampleHeader('2. Filter Accounts by Country');

try {
    $accounts = $client->accounts->all([
        'limit' => 5,
        'query' => 'country=es',
    ]);

    echo "Spanish accounts: {$accounts->getTotal()}\n";
    foreach ($accounts as $account) {
        echo "  - {$account['name']} ({$account['tin_value']})\n";
    }
} catch (ApiErrorException $e) {
    echo "Error filtering accounts: {$e->getMessage()}\n";
}

// ============================================================
// 3. Retrieve a specific account
// ============================================================

exampleHeader('3. Retrieve Account');

$accountId = env('B2B_ACCOUNT_ID');

try {
    $account = $client->accounts->retrieve($accountId);

    echo "Account details:\n";
    echo "  ID:       {$account['id']}\n";
    echo "  Name:     {$account['name']}\n";
    echo "  TIN:      " . ($account['tin_value'] ?? 'N/A') . "\n";
    echo "  Country:  " . ($account['country'] ?? 'N/A') . "\n";
    echo "  Email:    " . ($account['email'] ?? 'N/A') . "\n";
    echo "  Has Logo: " . ($account['has_logo'] ? 'Yes' : 'No') . "\n";
} catch (ApiErrorException $e) {
    echo "Error retrieving account: {$e->getMessage()}\n";
}

// ============================================================
// Note: Create, update, and delete operations are commented out
// to avoid modifying your account. Uncomment to test.
// ============================================================


// 4. Create a new account (eDocSync subscriptions only)
$newAccount = $client->accounts->create([
    'account' => [
        'name' => 'Test Account',
        'tin_value' => 'ES26184309M',
        'email' => 'test@example.com',
        'phone' => '+34600000000',
        'address' => 'Calle Test 1',
        'city' => 'Madrid',
        'postalcode' => '28001',
        'province' => 'Madrid',
        'country' => 'es',
    ]
]);
echo "Created account: {$newAccount['id']}\n";

// 5. Update an account
$updated = $client->accounts->update($newAccount['id'], [
    'account' => ['name' => 'Updated Test Account']
]);
echo "Updated name: {$updated['name']}\n";
/*
// 6. Delete (archive) an account
$deleted = $client->accounts->delete($newAccount['id']);
echo "Deleted: " . ($deleted['deleted'] ? 'yes' : 'archived') . "\n";

// 7. Unarchive an account
$unarchived = $client->accounts->unarchive($newAccount['id']);
echo "Unarchived: {$unarchived['name']}\n";

// 8. Upload a logo
$logoData = file_get_contents('/path/to/logo.png');
$withLogo = $client->accounts->uploadLogo($newAccount['id'], $logoData);
echo "Has logo: " . ($withLogo['has_logo'] ? 'yes' : 'no') . "\n";

// 9. Delete a logo
$withoutLogo = $client->accounts->deleteLogo($newAccount['id']);
echo "Has logo: " . ($withoutLogo['has_logo'] ? 'yes' : 'no') . "\n";
*/
