<?php
/**
 * Example: Using a previous API version
 *
 * The SDK defaults to the latest API version (2026-04-20). If your code
 * depends on behavior from a previous API version, you can pin it explicitly.
 *
 * This is useful when:
 * - You've upgraded the SDK but aren't ready to migrate to the new API version
 * - You need time to update code that uses removed or renamed fields
 * - You want to test both API versions side by side
 */

require_once __DIR__ . '/bootstrap.php';

use B2BRouter\B2BRouterClient;

checkRequiredEnv();

$accountId = env('B2B_ACCOUNT_ID');

// ============================================================
// Client using the current API version (default)
// ============================================================

$currentClient = new B2BRouterClient(env('B2B_API_KEY'), [
    'api_version' => env('B2B_API_VERSION', '2026-04-20'),
    'api_base' => env('B2B_API_BASE'),
]);

echo "=== Current API version: {$currentClient->getApiVersion()} ===\n\n";

try {
    $invoices = $currentClient->invoices->all($accountId, ['limit' => 3]);
    echo "Invoices found: {$invoices->getTotal()}\n";

    // In API 2026-04-20, amend metadata is structured under invoice_references[]:
    // $invoice = $currentClient->invoices->create($accountId, [
    //     'invoice' => [
    //         // ...
    //         'invoice_references' => [
    //             ['reference_type' => 'amend', 'number' => 'INV-001', 'date' => '2025-01-15'],
    //         ],
    //     ],
    // ]);
} catch (\Exception $e) {
    echo "Error: {$e->getMessage()}\n";
}

// ============================================================
// Client pinned to a previous API version
// ============================================================

$previousClient = new B2BRouterClient(env('B2B_API_KEY'), [
    'api_version' => '2026-03-02',
    'api_base' => env('B2B_API_BASE'),
]);

echo "\n=== Previous API version: {$previousClient->getApiVersion()} ===\n\n";

try {
    $invoices = $previousClient->invoices->all($accountId, ['limit' => 3]);
    echo "Invoices found: {$invoices->getTotal()}\n";

    // In API 2026-03-02, flat amend fields are still accepted:
    // $invoice = $previousClient->invoices->create($accountId, [
    //     'invoice' => [
    //         // ...
    //         'amended_number' => 'INV-001',
    //         'amended_date' => '2025-01-15',
    //     ],
    // ]);
} catch (\Exception $e) {
    echo "Error: {$e->getMessage()}\n";
}

echo "\nBoth API versions work with the same SDK.\n";
echo "Pin your version until you're ready to migrate.\n";
