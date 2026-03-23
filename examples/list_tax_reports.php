<?php
/**
 * List Tax Reports Example
 *
 * This example demonstrates how to list, filter, and paginate through tax reports.
 *
 * Setup:
 *   1. Copy .env.example to .env
 *   2. Add your B2B_API_KEY and B2B_ACCOUNT_ID
 *   3. Run: php examples/list_tax_reports.php
 */

require_once __DIR__ . '/bootstrap.php';

use B2BRouter\B2BRouterClient;
use B2BRouter\Exception\ApiErrorException;

// Check required environment variables
checkRequiredEnv();

// Initialize the client
$client = new B2BRouterClient(env('B2B_API_KEY'), [
    'api_version' => env('B2B_API_VERSION', '2025-10-13'),
    'api_base' => env('B2B_API_BASE'),
]);

$accountId = env('B2B_ACCOUNT_ID');

try {
    echo "=== List Tax Reports Example ===\n\n";

    // ============================================
    // Example 1: Basic listing
    // ============================================
    echo "Example 1: Basic listing (first 10 tax reports)...\n";

    $reports = $client->taxReports->all($accountId, [
        'limit' => 10,
        'offset' => 0
    ]);

    echo "Found {$reports->count()} tax reports\n";
    echo "Total available: {$reports->getTotal()}\n";
    echo "Has more: " . ($reports->hasMore() ? 'Yes' : 'No') . "\n\n";

    foreach ($reports as $report) {
        echo "  - ID: {$report['id']}\n";
        echo "    Label: {$report['label']}\n";
        echo "    Type: {$report['type']}\n";
        echo "    State: {$report['state']}\n";
        echo "    Invoice: {$report['invoice_number']} ({$report['invoice_date']})\n";
        echo "    Customer: {$report['customer_party_name']}\n";
        echo "    Amount: {$report['currency']} {$report['tax_inclusive_amount']}\n";
        echo "\n";
    }

    // ============================================
    // Example 2: Filter by date range
    // ============================================
    echo "Example 2: Filter by date range...\n";

    $dateFilteredReports = $client->taxReports->all($accountId, [
        'sent_at_from' => '2025-04-01',
        'limit' => 5
    ]);

    echo "Tax reports sent since 2025-04-01: {$dateFilteredReports->count()}\n";
    foreach ($dateFilteredReports as $report) {
        echo "  - {$report['label']}: sent at {$report['sent_at']}\n";
    }
    echo "\n";

    // ============================================
    // Example 3: Filter by invoice ID
    // ============================================
    echo "Example 3: Filter by invoice ID...\n";

    $invoiceId = 'inv_12345'; // Replace with actual invoice ID

    $invoiceReports = $client->taxReports->all($accountId, [
        'invoice_id' => $invoiceId
    ]);

    echo "Tax reports for invoice {$invoiceId}: {$invoiceReports->count()}\n";
    foreach ($invoiceReports as $report) {
        echo "  - {$report['label']}: {$report['state']}\n";
        if (isset($report['annullation']) && $report['annullation']) {
            echo "    (This is an annullation)\n";
        }
        if (isset($report['correction']) && $report['correction']) {
            echo "    (This is a correction)\n";
        }
    }
    echo "\n";

    // ============================================
    // Example 4: Filter by update date
    // ============================================
    echo "Example 4: Filter by update date...\n";

    $recentlyUpdated = $client->taxReports->all($accountId, [
        'updated_at_from' => '2025-04-10',
        'limit' => 10
    ]);

    echo "Tax reports updated since 2025-04-10: {$recentlyUpdated->count()}\n\n";

    // ============================================
    // Example 5: Pagination - Iterate through all
    // ============================================
    echo "Example 5: Paginating through all tax reports...\n";

    $limit = 25;
    $offset = 0;
    $allReports = [];
    $totalFetched = 0;

    do {
        echo "  Fetching page starting at offset {$offset}...\n";

        $page = $client->taxReports->all($accountId, [
            'limit' => $limit,
            'offset' => $offset
        ]);

        $pageCount = $page->count();
        $totalFetched += $pageCount;

        echo "    Retrieved {$pageCount} tax reports\n";

        // Collect all reports
        foreach ($page as $report) {
            $allReports[] = $report;
        }

        $offset += $limit;

    } while ($page->hasMore());

    echo "Total tax reports fetched: {$totalFetched}\n";
    echo "Total in collection: " . count($allReports) . "\n\n";

    // ============================================
    // Example 6: Analyze tax reports by state
    // ============================================
    echo "Example 6: Analyzing tax reports by state...\n";

    $allReportsForAnalysis = $client->taxReports->all($accountId, [
        'limit' => 100
    ]);

    $stateCount = [];
    foreach ($allReportsForAnalysis as $report) {
        $state = $report['state'];
        if (!isset($stateCount[$state])) {
            $stateCount[$state] = 0;
        }
        $stateCount[$state]++;
    }

    echo "Tax reports by state:\n";
    foreach ($stateCount as $state => $count) {
        echo "  {$state}: {$count}\n";
    }
    echo "\n";

    // ============================================
    // Example 7: Find specific tax reports
    // ============================================
    echo "Example 7: Finding specific types of tax reports...\n";

    $allForSearch = $client->taxReports->all($accountId, [
        'limit' => 50
    ]);

    $verifactuCount = 0;
    $ticketbaiCount = 0;
    $annullationCount = 0;
    $correctionCount = 0;

    foreach ($allForSearch as $report) {
        if ($report['type'] === 'Verifactu') {
            $verifactuCount++;
        }
        if ($report['type'] === 'TicketBai') {
            $ticketbaiCount++;
        }
        if (isset($report['annullation']) && $report['annullation']) {
            $annullationCount++;
        }
        if (isset($report['correction']) && $report['correction']) {
            $correctionCount++;
        }
    }

    echo "Type breakdown:\n";
    echo "  VeriFactu: {$verifactuCount}\n";
    echo "  TicketBAI: {$ticketbaiCount}\n";
    echo "  Annullations: {$annullationCount}\n";
    echo "  Corrections: {$correctionCount}\n\n";

    // ============================================
    // Example 8: Display pagination info
    // ============================================
    echo "Example 8: Working with pagination metadata...\n";

    $firstPage = $client->taxReports->all($accountId, [
        'limit' => 10,
        'offset' => 0
    ]);

    echo "Pagination info:\n";
    echo "  Current page items: {$firstPage->count()}\n";
    echo "  Total items: {$firstPage->getTotal()}\n";
    echo "  Offset: {$firstPage->getOffset()}\n";
    echo "  Limit: {$firstPage->getLimit()}\n";
    echo "  Has more: " . ($firstPage->hasMore() ? 'Yes' : 'No') . "\n";

    if ($firstPage->hasMore()) {
        $nextOffset = $firstPage->getOffset() + $firstPage->getLimit();
        echo "  Next offset would be: {$nextOffset}\n";
    }
    echo "\n";

    // ============================================
    // Tips and best practices
    // ============================================
    echo "=== Tips for listing tax reports ===\n";
    echo "1. Use appropriate limits to avoid overloading (max 500)\n";
    echo "2. Use date filters to narrow down results\n";
    echo "3. Check hasMore() to determine if pagination is needed\n";
    echo "4. Filter by invoice_id to see all related tax reports\n";
    echo "5. Monitor 'state' field to track processing status\n";
    echo "6. Look for 'annullation' and 'correction' flags\n\n";

    echo "=== List Example Complete ===\n";

} catch (ApiErrorException $e) {
    echo "\nAPI Error: {$e->getMessage()}\n";
    echo "HTTP Status: {$e->getHttpStatus()}\n";

    if ($e->getRequestId()) {
        echo "Request ID: {$e->getRequestId()}\n";
    }

    if ($e->getJsonBody()) {
        echo "\nError details:\n";
        echo json_encode($e->getJsonBody(), JSON_PRETTY_PRINT) . "\n";
    }
} catch (\Exception $e) {
    echo "\nError: {$e->getMessage()}\n";
}
