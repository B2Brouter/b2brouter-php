<?php
/**
 * Example: Account management (eDocSync)
 *
 * Demonstrates the full account lifecycle available to eDocSync subscriptions:
 * create, update, upload/delete logo, list, retrieve, delete, and unarchive.
 *
 * eDocSync subscriptions can create accounts via API.
 */

require_once __DIR__ . '/bootstrap.php';

use B2BRouter\B2BRouterClient;
use B2BRouter\Exception\ApiErrorException;

checkRequiredEnv(['B2B_API_KEY']);

$client = new B2BRouterClient(env('B2B_API_KEY'), [
    'api_version' => env('B2B_API_VERSION', '2026-03-02'),
    'api_base' => env('B2B_API_BASE'),
]);

// ============================================================
// 1. Create an account
// ============================================================

exampleHeader('1. Create Account');

try {
    $account = $client->accounts->create([
        'account' => [
            'name' => 'SDK Test Account ' . date('His'),
            'tin_value' => 'ES26184309M',
            'email' => 'sdk-test@example.com',
            'phone' => '+34600000000',
            'address' => 'Calle Test 1',
            'city' => 'Madrid',
            'postalcode' => '28001',
            'province' => 'Madrid',
            'country' => 'es',
        ]
    ]);
    echo "Created account: {$account['id']} ({$account['name']})\n";

    // ============================================================
    // 2. Update the account
    // ============================================================

    exampleHeader('2. Update Account');

    $updated = $client->accounts->update($account['id'], [
        'account' => [
            'name' => 'SDK Test Account Updated',
            'phone' => '+34600000001',
        ]
    ]);
    echo "Updated: {$updated['name']} (phone: {$updated['phone']})\n";

    // ============================================================
    // 3. Upload a logo
    // ============================================================

    exampleHeader('3. Upload Logo');

    $logoData = file_get_contents(__DIR__ . '/logo.png');
    $withLogo = $client->accounts->uploadLogo($account['id'], $logoData);
    echo "Has logo: " . ($withLogo['has_logo'] ? 'yes' : 'no') . "\n";

    // ============================================================
    // 4. Delete the logo
    // ============================================================

    exampleHeader('4. Delete Logo');

    $withoutLogo = $client->accounts->deleteLogo($account['id']);
    echo "Has logo: " . ($withoutLogo['has_logo'] ? 'yes' : 'no') . "\n";

    // ============================================================
    // 5. List accounts
    // ============================================================

    exampleHeader('5. List Accounts');

    $accounts = $client->accounts->all(['limit' => 10]);

    echo "Found {$accounts->count()} accounts";
    if ($accounts->getTotal()) {
        echo " (Total: {$accounts->getTotal()})";
    }
    echo "\n\n";

    foreach ($accounts as $a) {
        printf("  ID: %-10s Name: %-30s TIN: %s\n",
            $a['id'],
            $a['name'] ?? 'N/A',
            $a['tin_value'] ?? 'N/A'
        );
    }

    // ============================================================
    // 6. Retrieve the created account
    // ============================================================

    exampleHeader('6. Retrieve Account');

    $retrieved = $client->accounts->retrieve($account['id']);
    echo "Account details:\n";
    echo "  ID:       {$retrieved['id']}\n";
    echo "  Name:     {$retrieved['name']}\n";
    echo "  TIN:      " . ($retrieved['tin_value'] ?? 'N/A') . "\n";
    echo "  Country:  " . ($retrieved['country'] ?? 'N/A') . "\n";
    echo "  Email:    " . ($retrieved['email'] ?? 'N/A') . "\n";
    echo "  Has Logo: " . ($retrieved['has_logo'] ? 'Yes' : 'No') . "\n";

    // ============================================================
    // 7. Delete (archive) the account
    // ============================================================

    exampleHeader('7. Delete Account');

    $deleted = $client->accounts->delete($account['id']);
    echo "Deleted account: {$deleted['id']}\n";

    // ============================================================
    // 8. Unarchive the account
    // ============================================================

    exampleHeader('8. Unarchive Account');

    $unarchived = $client->accounts->unarchive($account['id']);
    echo "Unarchived: {$unarchived['id']} ({$unarchived['name']})\n";

    // Clean up: delete again so we don't leave test data behind
    $client->accounts->delete($account['id']);
    echo "\nCleaned up: account deleted.\n";

} catch (ApiErrorException $e) {
    echo "Error: {$e->getMessage()}\n";
    if ($e->getRequestId()) {
        echo "Request ID: {$e->getRequestId()}\n";
    }
}
