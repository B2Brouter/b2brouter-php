<?php
/**
 * VeriFactu Tax Report Example
 *
 * This example demonstrates how to create and manage VeriFactu tax reports
 * for compliance with Spain's Anti-Fraud Law.
 *
 * Key VeriFactu characteristics:
 * - Uses tax breakdowns (aggregated tax totals, not individual lines)
 * - Supports corrections (subsanación) via update/PATCH
 * - Supports annullations (anulación) via delete/DELETE
 * - QR code is immediately available in the create response
 * - Chaining and sending to AEAT happens asynchronously in the background
 *
 * Setup:
 *   1. Copy .env.example to .env
 *   2. Add your B2B_API_KEY and B2B_ACCOUNT_ID
 *   3. Run: php examples/verifactu_tax_report.php
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
    echo "=== VeriFactu Tax Report Example ===\n\n";

    // ============================================
    // Step 0: Check if VeriFactu is configured
    // ============================================
    echo "Checking if VeriFactu is configured for this account...\n";

    try {
        $verifactuSettings = $client->taxReportSettings->retrieve($accountId, 'verifactu');
        echo "✓ VeriFactu is configured\n";
        echo "  Auto generate: " . ($verifactuSettings['auto_generate'] ? 'Yes' : 'No') . "\n";
        echo "  Auto send: " . ($verifactuSettings['auto_send'] ? 'Yes' : 'No') . "\n\n";
    } catch (ApiErrorException $e) {
        if ($e->getHttpStatus() === 404) {
            echo "✗ VeriFactu is NOT configured for this account!\n";
            echo "  Please run 'php examples/tax_report_setup.php' first to configure VeriFactu.\n\n";
            exit(1);
        } else {
            throw $e; // Re-throw if it's a different error
        }
    }

    // ============================================
    // Step 1: Create a VeriFactu Tax Report
    // ============================================
    echo "Creating VeriFactu tax report...\n";

    // Generate random invoice number to avoid duplicates
    $randomNumber = rand(1000, 9999);
    $invoiceNumber = "2025-VF-{$randomNumber}";
    echo "Using invoice number: {$invoiceNumber}\n\n";

    $taxReport = $client->taxReports->create($accountId, [
        'tax_report' => [
            // Required: Tax report type
            'type' => 'Verifactu',

            // Required: Invoice information
            'invoice_date' => '2025-11-15',
            'invoice_number' => $invoiceNumber,
            'invoice_type_code' => 'F1', // F1 = Standard invoice

            // Optional: Invoice series
            // 'invoice_series_code' => 'A',

            // Required: Description
            'description' => 'Professional consulting services for Q1 2025',

            // Required: Customer information
            'customer_party_name' => 'Empresa Ejemplo S.L.',
            'customer_party_tax_id' => 'P9109010J',
            'customer_party_country' => 'es',

            // Required: Amounts
            'tax_inclusive_amount' => 121.0,  // Total including tax
            'tax_amount' => 21.0,              // Total tax amount
            'currency' => 'EUR',

            // Required: Tax breakdowns (aggregated by tax type)
            // Note: VeriFactu uses breakdowns, not individual lines
            'tax_breakdowns' => [
                [
                    'name' => 'IVA',
                    'category' => 'S',               // S = Subject to tax
                    'non_exemption_code' => 'S1',    // S1 = Subject without reverse charge
                    'percent' => 21.0,
                    'taxable_base' => 100.0,
                    'tax_amount' => 21.0,
                    'special_regime_key' => '01'     // 01 = General regime
                ]
            ],

            // Optional: Additional fields
            // 'external_reference' => 'YOUR-REF-123',
            // 'tax_point_date' => '2025-04-14', // Operation date if different from invoice date
        ]
    ]);

    echo "Tax report created successfully!\n";
    echo "  ID: {$taxReport['id']}\n";
    echo "  Label: {$taxReport['label']}\n";
    echo "  State: {$taxReport['state']}\n";
    echo "  Invoice number: {$taxReport['invoice_number']}\n";

    if (isset($taxReport['qr'])) {
        echo "  QR code: Available (base64 encoded)\n";
        echo "  The QR code is immediately available for VeriFactu!\n";
        echo "  Include this QR code in the invoice you send to your customer.\n";
    }

    if (isset($taxReport['identifier'])) {
        echo "  Identifier URL: {$taxReport['identifier']}\n";
        echo "  Your customer can verify the invoice at this URL.\n";
    }
    echo "\n";

    $taxReportId = $taxReport['id'];

    // ============================================
    // Step 2: Monitor the tax report state
    // ============================================
    echo "Monitoring tax report state...\n";
    echo "Note: The QR code is already available above!\n";
    echo "You need to monitor the state to know when it's been sent to AEAT.\n";
    echo "(In production, use webhooks instead of polling)\n\n";

    $finalStates = ['registered', 'error', 'registered_with_errors'];
    $maxAttempts = 10;

    for ($i = 0; $i < $maxAttempts; $i++) {
        sleep(2); // Wait 2 seconds between checks

        $status = $client->taxReports->retrieve($taxReportId);
        $checkNum = $i + 1;
        echo "  Check {$checkNum}: State = {$status['state']}";

        if (isset($status['chained_at'])) {
            echo " (Chained at: {$status['chained_at']})";
        }

        if (isset($status['sent_at'])) {
            echo " (Sent at: {$status['sent_at']})";
        }

        echo "\n";

        if (in_array($status['state'], $finalStates)) {
            echo "\nTax report reached final state: {$status['state']}\n\n";

            if ($status['state'] === 'registered') {
                echo "Successfully registered with AEAT!\n";
                echo "The tax report has been chained and sent to the Tax Authority.\n";
            } elseif ($status['state'] === 'error') {
                echo "Error registering tax report!\n";
                if ($status['has_errors']) {
                    echo "Check the 'errors' field for details\n";
                }
            }

            break;
        }
    }
    echo "\n";

    // ============================================
    // Step 3: Download the XML
    // ============================================
    echo "Downloading tax report XML...\n";

    try {
        $xml = $client->taxReports->download($taxReportId);
        echo "XML downloaded successfully (" . strlen($xml) . " bytes)\n";

        // Optionally save to file
        // file_put_contents("tax_report_{$taxReportId}.xml", $xml);
        // echo "Saved to tax_report_{$taxReportId}.xml\n";

    } catch (ApiErrorException $e) {
        echo "Could not download XML: {$e->getMessage()}\n";
        echo "The XML might not be ready yet (tax report needs to be chained first)\n";
    }
    echo "\n";

    // ============================================
    // Step 4: Create a correction (subsanación)
    // ============================================
    echo "Creating a correction...\n";
    echo "Note: Only use this if you need to correct an already-registered tax report\n\n";

    try {
        $correction = $client->taxReports->update($taxReportId, [
            'tax_report' => [
                'description' => 'CORRECTED: new description',
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

        echo "Correction created successfully!\n";
        echo "  Correction ID: {$correction['id']}\n";
        echo "  Correction flag: " . ($correction['correction'] ? 'Yes' : 'No') . "\n";
        echo "  State: {$correction['state']}\n\n";

    } catch (ApiErrorException $e) {
        echo "Could not create correction: {$e->getMessage()}\n\n";
    }

    // ============================================
    // Step 5: Create an annullation (anulación)
    // ============================================
    echo "Creating an annullation...\n";
    echo "Note: This cancels the original tax report by sending an annullation to AEAT\n\n";

    try {
        $annullation = $client->taxReports->delete($taxReportId);

        echo "Annullation created successfully!\n";
        echo "  Annullation ID: {$annullation['id']}\n";
        echo "  Annullation flag: " . ($annullation['annullation'] ? 'Yes' : 'No') . "\n";
        echo "  State: {$annullation['state']}\n\n";

        // Monitor annullation state
        echo "Monitoring annullation state...\n";
        for ($i = 0; $i < 5; $i++) {
            sleep(2);

            $annulStatus = $client->taxReports->retrieve($annullation['id']);
            $checkNum = $i + 1;
            echo "  Check {$checkNum}: State = {$annulStatus['state']}\n";

            if (in_array($annulStatus['state'], ['annulled', 'error'])) {
                echo "\nAnnullation reached final state: {$annulStatus['state']}\n";
                break;
            }
        }

    } catch (ApiErrorException $e) {
        echo "Could not create annullation: {$e->getMessage()}\n";
    }
    echo "\n";

    echo "=== VeriFactu Example Complete ===\n";

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
    echo $e->getTraceAsString() . "\n";
}
