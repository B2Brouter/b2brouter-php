<?php

require_once __DIR__ . '/bootstrap.php';

use B2BRouter\B2BRouterClient;

$client = new B2BRouterClient(env('B2B_API_KEY'), [
    'api_version' => env('B2B_API_VERSION', '2025-10-13'),
    'api_base' => env('B2B_API_BASE'),
]);

$accountId = env('B2B_ACCOUNT_ID');

try {
    $offset = 0;
    $limit = 50;
    $allInvoices = [];
    $pageCount = 0;

    echo "Fetching all invoices...\n";

    do {
        $pageCount++;
        echo "  Page {$pageCount}... ";

        $page = $client->invoices->all($accountId, [
            'limit' => $limit,
            'offset' => $offset,
        ]);

        $count = $page->count();
        echo "{$count} invoices\n";

        foreach ($page as $invoice) {
            $allInvoices[] = $invoice;
        }

        $offset += $limit;

        // Optional: Add delay to respect rate limits
        usleep(100000); // 100ms

    } while ($page->hasMore());

    echo "\n✓ Fetched all " . count($allInvoices) . " invoices!\n";

    // Analyze invoices
    $totalAmount = 0;
    $currencies = [];

    foreach ($allInvoices as $invoice) {
        $currency = $invoice['currency'] ?? 'EUR';
        $amount = $invoice['total'] ?? 0;

        if (!isset($currencies[$currency])) {
            $currencies[$currency] = 0;
        }
        $currencies[$currency] += $amount;
    }

    echo "\nSummary by currency:\n";
    foreach ($currencies as $currency => $amount) {
        echo "  {$currency}: " . number_format($amount, 2) . "\n";
    }

} catch (Exception $e) {
    echo "✗ Error: {$e->getMessage()}\n";
    exit(1);
}
