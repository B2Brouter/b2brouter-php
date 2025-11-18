<?php
/**
 * Tax Reports - Complete CRUD Example
 *
 * Demonstrates all tax report operations for VeriFactu:
 * - Create tax reports
 * - Retrieve and monitor state
 * - List with filters
 * - Download XML
 * - Update/correct (subsanación)
 * - Delete/annulate (anulación)
 *
 * Note: An account can only have one tax report type configured at a time.
 * This example uses VeriFactu. For TicketBAI examples, see ticketbai_tax_report.php
 *
 * Setup:
 *   1. Copy .env.example to .env
 *   2. Add your B2B_API_KEY and B2B_ACCOUNT_ID
 *   3. Configure VeriFactu: php examples/tax_report_setup.php
 *   4. Run: php examples/tax_reports.php
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
    echo "=== B2BRouter Tax Report Examples (VeriFactu) ===\n\n";

    // ============================================
    // Step 0: Check if VeriFactu is configured
    // ============================================
    echo "0. Checking if VeriFactu is configured...\n";

    try {
        $verifactuSettings = $client->taxReportSettings->retrieve($accountId, 'verifactu');
        echo "✓ VeriFactu is configured\n\n";
    } catch (ApiErrorException $e) {
        if ($e->getHttpStatus() === 404) {
            echo "✗ VeriFactu is NOT configured for this account!\n";
            echo "  Please run 'php examples/tax_report_setup.php' first to configure VeriFactu.\n\n";
            exit(1);
        } else {
            throw $e;
        }
    }

    // ============================================
    // 1. Create a VeriFactu Tax Report
    // ============================================
    echo "1. Creating a VeriFactu tax report...\n";

    // Generate random invoice number to avoid duplicates
    $randomNumber = rand(1000, 9999);
    $invoiceNumber = "2025-VF-{$randomNumber}";
    echo "Using invoice number: {$invoiceNumber}\n";

    $verifactuReport = $client->taxReports->create($accountId, [
        'tax_report' => [
            'type' => 'Verifactu',
            'invoice_date' => '2025-04-03',
            'invoice_number' => $invoiceNumber,
            'description' => 'Professional consulting services',
            'customer_party_tax_id' => 'P9109010J',
            'customer_party_country' => 'es',
            'customer_party_name' => 'Ejemplo S.L.',
            'tax_inclusive_amount' => 121.0,
            'tax_amount' => 21.0,
            'invoice_type_code' => 'F1',
            'currency' => 'EUR',
            'tax_breakdowns' => [
                [
                    'name' => 'IVA',
                    'category' => 'S',
                    'non_exemption_code' => 'S1',
                    'percent' => 21.0,
                    'taxable_base' => 100.0,
                    'tax_amount' => 21.0,
                    'special_regime_key' => '01'
                ]
            ]
        ]
    ]);

    echo "VeriFactu tax report created with ID: {$verifactuReport['id']}\n";
    echo "State: {$verifactuReport['state']}\n";
    echo "QR code available: " . (isset($verifactuReport['qr']) ? 'Yes' : 'No') . "\n";
    if (isset($verifactuReport['qr'])) {
        echo "QR code is immediately available for VeriFactu - add it to your invoice!\n";
    }
    echo "\n";

    // ============================================
    // 2. Retrieve a tax report
    // ============================================
    echo "2. Retrieving the VeriFactu tax report...\n";
    $taxReportId = $verifactuReport['id'];
    $retrievedReport = $client->taxReports->retrieve($taxReportId);
    echo "Retrieved tax report: {$retrievedReport['label']}\n";
    echo "Invoice number: {$retrievedReport['invoice_number']}\n";
    echo "Current state: {$retrievedReport['state']}\n\n";

    // ============================================
    // 3. Monitor tax report state (polling)
    // ============================================
    echo "3. Monitoring tax report state...\n";
    echo "Note: In production, use webhooks instead of polling\n";

    $maxAttempts = 10;
    $attempt = 0;
    $finalStates = ['registered', 'error', 'registered_with_errors', 'annulled'];

    while ($attempt < $maxAttempts) {
        $attempt++;
        $status = $client->taxReports->retrieve($taxReportId);

        echo "  Attempt {$attempt}: State = {$status['state']}\n";

        if (in_array($status['state'], $finalStates)) {
            echo "  Tax report reached final state: {$status['state']}\n";
            if ($status['state'] === 'registered') {
                echo "  Successfully registered with Tax Authority!\n";
                if (isset($status['qr'])) {
                    echo "  QR code is now available\n";
                }
            }
            break;
        }

        sleep(2); // Wait 2 seconds before next check
    }
    echo "\n";

    // ============================================
    // 4. List tax reports with filters
    // ============================================
    echo "4. Listing tax reports...\n";
    $reports = $client->taxReports->all($accountId, [
        'limit' => 10,
        'offset' => 0,
    ]);

    echo "Found {$reports->count()} tax reports (Total: {$reports->getTotal()})\n";
    foreach ($reports as $report) {
        echo "  - {$report['label']}: {$report['state']}\n";
    }

    if ($reports->hasMore()) {
        echo "There are more tax reports available\n";
    }
    echo "\n";

    // ============================================
    // 5. List with date filters
    // ============================================
    echo "5. Listing tax reports with date filters...\n";
    $filteredReports = $client->taxReports->all($accountId, [
        'updated_at_from' => '2025-04-01',
        'limit' => 5,
    ]);
    echo "Found {$filteredReports->count()} tax reports updated since 2025-04-01\n\n";

    // ============================================
    // 6. Download tax report XML
    // ============================================
    echo "6. Downloading tax report XML...\n";
    try {
        $xml = $client->taxReports->download($taxReportId);
        echo "XML downloaded successfully (" . strlen($xml) . " bytes)\n";
        echo "First 100 characters: " . substr($xml, 0, 100) . "...\n\n";
    } catch (ApiErrorException $e) {
        echo "Could not download XML (might not be ready yet): {$e->getMessage()}\n\n";
    }

    // ============================================
    // 7. Update/Correct a tax report (VeriFactu only)
    // ============================================
    echo "7. Correcting the VeriFactu tax report...\n";
    echo "Note: This creates a correction ('subsanación') for VeriFactu\n";

    try {
        $correctedReport = $client->taxReports->update($taxReportId, [
            'tax_report' => [
                'description' => 'Corrected: Professional consulting services - Updated description',
                'tax_breakdowns' => [
                    [
                        'name' => 'IVA',
                        'category' => 'S',
                        'non_exemption_code' => 'S1',
                        'percent' => 21.0,
                        'taxable_base' => 100.0,
                        'tax_amount' => 21.0,
                        'special_regime_key' => '04'
                    ]
                ]
            ]
        ]);

        echo "Correction created with ID: {$correctedReport['id']}\n";
        echo "Correction field: " . ($correctedReport['correction'] ? 'Yes' : 'No') . "\n\n";
    } catch (ApiErrorException $e) {
        echo "Could not create correction: {$e->getMessage()}\n\n";
    }

    // ============================================
    // 8. Delete/Annulate a tax report
    // ============================================
    echo "8. Annulating a tax report...\n";
    echo "Note: This creates an annullation ('anulación') for VeriFactu\n";

    try {
        $annullation = $client->taxReports->delete($taxReportId);

        echo "Annullation created with ID: {$annullation['id']}\n";
        echo "Annullation field: " . ($annullation['annullation'] ? 'Yes' : 'No') . "\n";
        echo "State: {$annullation['state']}\n\n";

        // Monitor the annullation state
        echo "  Monitoring annullation state...\n";
        $maxAttempts = 5;
        for ($i = 0; $i < $maxAttempts; $i++) {
            $annulStatus = $client->taxReports->retrieve($annullation['id']);
            echo "    Attempt " . ($i + 1) . ": State = {$annulStatus['state']}\n";

            if (in_array($annulStatus['state'], ['annulled', 'error'])) {
                echo "    Annullation reached final state: {$annulStatus['state']}\n";
                break;
            }

            sleep(2);
        }

    } catch (ApiErrorException $e) {
        echo "Could not annulate: {$e->getMessage()}\n";
    }
    echo "\n";

    echo "=== All operations completed successfully ===\n";

} catch (ApiErrorException $e) {
    echo "API Error: {$e->getMessage()}\n";
    echo "HTTP Status: {$e->getHttpStatus()}\n";

    if ($e->getRequestId()) {
        echo "Request ID: {$e->getRequestId()}\n";
    }

    if ($e->getJsonBody()) {
        echo "Error details: " . json_encode($e->getJsonBody(), JSON_PRETTY_PRINT) . "\n";
    }
} catch (\Exception $e) {
    echo "Error: {$e->getMessage()}\n";
}
