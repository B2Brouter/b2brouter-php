<?php

require_once __DIR__ . '/bootstrap.php';

use B2BRouter\B2BRouterClient;

$client = new B2BRouterClient(env('B2B_API_KEY'), [
    'api_version' => env('B2B_API_VERSION', '2025-10-13'),
    'api_base' => env('B2B_API_BASE'),
]);

$accountId = env('B2B_ACCOUNT_ID');

try {
    echo "Fetching invoices from the last 30 days...\n\n";

    $invoices = $client->invoices->all($accountId, [
        'limit' => 25,
        'offset' => 0,
        'date_from' => date('Y-m-d', strtotime('-30 days')),
        'date_to' => date('Y-m-d'),
    ]);

    echo "Found {$invoices->count()} invoices";
    if ($invoices->getTotal()) {
        echo " (Total: {$invoices->getTotal()})";
    }
    echo "\n\n";

    // Display invoices in a table
    printf("%-15s %-20s %-12s %-10s %s\n",
        'ID', 'Number', 'Date', 'Amount', 'Status');
    echo str_repeat('-', 80) . "\n";

    foreach ($invoices as $invoice) {
        printf("%-15s %-20s %-12s %-10s %s\n",
            substr((string)$invoice['id'], 0, 12) . '...',
            $invoice['number'] ?? 'N/A',
            $invoice['date'] ?? 'N/A',
            $invoice['currency'] . ' ' . number_format($invoice['total'] ?? 0, 2),
            $invoice['state'] ?? 'unknown'
        );
    }

    echo "\n";

    // Check for more pages
    if ($invoices->hasMore()) {
        echo "Note: More invoices available. Use offset parameter to fetch next page.\n";
        echo "Example: \$invoices = \$client->invoices->all(\$accountId, ['offset' => 25]);\n";
    }

} catch (Exception $e) {
    echo "✗ Error: {$e->getMessage()}\n";
    exit(1);
}
