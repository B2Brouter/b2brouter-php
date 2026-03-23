<?php
/**
 * TicketBAI Tax Report Example
 *
 * This example demonstrates how to create and manage TicketBAI tax reports
 * for compliance with Basque Country tax requirements.
 *
 * Key TicketBAI characteristics:
 * - Uses tax_report_lines (individual invoice lines) + tax breakdowns
 * - Supports annullations (anulación) via delete/DELETE
 * - Corrections (Zuzendu) are NOT currently supported
 * - QR code is generated AFTER chaining (poll state to get it)
 * - Chaining process is asynchronous
 *
 * Setup:
 *   1. Copy .env.example to .env
 *   2. Add your B2B_API_KEY and B2B_ACCOUNT_ID
 *   3. Run: php examples/ticketbai_tax_report.php
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
    echo "=== TicketBAI Tax Report Example ===\n\n";

    // ============================================
    // Step 0: Check if TicketBAI is configured
    // ============================================
    echo "Checking if TicketBAI is configured for this account...\n";

    try {
        $ticketbaiSettings = $client->taxReportSettings->retrieve($accountId, 'tbai');
        echo "✓ TicketBAI is configured\n";
        echo "  Delegation: " . ($ticketbaiSettings['delegation'] ?? 'Not set') . "\n";
        echo "  Auto generate: " . ($ticketbaiSettings['auto_generate'] ? 'Yes' : 'No') . "\n";
        echo "  Auto send: " . ($ticketbaiSettings['auto_send'] ? 'Yes' : 'No') . "\n\n";
    } catch (ApiErrorException $e) {
        if ($e->getHttpStatus() === 404) {
            echo "✗ TicketBAI is NOT configured for this account!\n";
            echo "  Please configure TicketBAI settings first.\n";
            echo "  Refer to ticketbai.md for configuration instructions.\n\n";
            exit(1);
        } else {
            throw $e; // Re-throw if it's a different error
        }
    }

    // ============================================
    // Step 1: Create a TicketBAI Tax Report
    // ============================================
    echo "Creating TicketBAI tax report...\n";

    // Generate random invoice number to avoid duplicates
    $randomNumber = rand(1000, 9999);
    $invoiceNumber = "2025-TB-{$randomNumber}";
    echo "Using invoice number: {$invoiceNumber}\n\n";

    $taxReport = $client->taxReports->create($accountId, [
        'tax_report' => [
            // Required: Tax report type
            'type' => 'TicketBai',

            // Required: Invoice information
            'invoice_date' => '2025-04-15',
            'invoice_number' => $invoiceNumber,
            'invoice_type_code' => 'F1', // F1 = Standard invoice

            // Optional: Invoice series
            // 'invoice_series_code' => 'A',

            // Required: Description
            'description' => 'Sale of products and services',

            // Required: Customer information
            'customer_party_name' => 'Cliente Ejemplo S.L.',
            'customer_party_tax_id' => 'B12345678',
            'customer_party_country' => 'es',
            'customer_party_postalcode' => '48010',
            'customer_party_address' => 'Calle Falsa 123, 3ºA',

            // Required: Amounts
            'tax_inclusive_amount' => 121.0,  // Total including tax
            'tax_amount' => 21.0,              // Total tax amount
            'currency' => 'EUR',

            // Required for TicketBAI: Individual invoice lines
            'tax_report_lines' => [
                [
                    'quantity' => 2.0,
                    'description' => 'Product A - Premium',
                    'price' => 50.0,
                    'tax_inclusive_amount' => 121.0,
                    'tax_exclusive_amount' => 100.0,
                    'tax_amount' => 21.0
                ]
            ],

            // Required: Tax breakdowns (aggregated tax totals)
            'tax_breakdowns' => [
                [
                    'category' => 'S',               // S = Subject to tax
                    'non_exempt' => true,
                    'non_exemption_code' => 'S1',    // S1 = Subject without reverse charge
                    'percent' => 21.0,
                    'taxable_base' => 100.0,
                    'tax_amount' => 21.0
                ]
            ],

            // Optional: Additional fields
            // 'external_reference' => 'YOUR-REF-123',
            // 'tax_point_date' => '2025-04-14', // Operation date if different
        ]
    ]);

    echo "Tax report created successfully!\n";
    echo "  ID: {$taxReport['id']}\n";
    echo "  Label: {$taxReport['label']}\n";
    echo "  State: {$taxReport['state']}\n";
    echo "  Invoice number: {$taxReport['invoice_number']}\n";
    echo "  QR code: " . (isset($taxReport['qr']) ? 'Available' : 'Not yet available') . "\n";

    if (!isset($taxReport['qr'])) {
        echo "  Note: For TicketBAI, the QR code is generated after chaining.\n";
        echo "  You must poll the state to retrieve it once the tax report is chained.\n";
    }

    if (isset($taxReport['identifier'])) {
        echo "  Identifier: {$taxReport['identifier']}\n";
    }
    echo "\n";

    $taxReportId = $taxReport['id'];

    // ============================================
    // Step 2: Monitor the tax report state
    // ============================================
    echo "Monitoring tax report state...\n";
    echo "Important: For TicketBAI, you MUST poll until the QR code is available!\n";
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

        if (isset($status['qr'])) {
            echo " [QR available!]";
        }

        echo "\n";

        if (in_array($status['state'], $finalStates)) {
            echo "\nTax report reached final state: {$status['state']}\n\n";

            if ($status['state'] === 'registered') {
                echo "Successfully registered with Basque Tax Authority!\n";

                if (isset($status['qr'])) {
                    echo "QR code is now available!\n";
                    echo "Include this QR code in the invoice sent to your customer.\n";
                } else {
                    echo "Warning: QR code not available yet, may need more time.\n";
                }

                if (isset($status['identifier'])) {
                    echo "Identifier: {$status['identifier']}\n";
                }
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
        // file_put_contents("ticketbai_{$taxReportId}.xml", $xml);
        // echo "Saved to ticketbai_{$taxReportId}.xml\n";

    } catch (ApiErrorException $e) {
        echo "Could not download XML: {$e->getMessage()}\n";
        echo "The XML might not be ready yet (tax report needs to be chained first)\n";
    }
    echo "\n";

    // ============================================
    // Step 4: Complex example with multiple lines
    // ============================================
    echo "Creating a more complex TicketBAI with multiple lines...\n";

    // Generate another random invoice number
    $randomNumber2 = rand(1000, 9999);
    $invoiceNumber2 = "2025-TB-{$randomNumber2}";
    echo "Using invoice number: {$invoiceNumber2}\n\n";

    $complexReport = $client->taxReports->create($accountId, [
        'tax_report' => [
            'type' => 'TicketBai',
            'invoice_date' => '2025-04-16',
            'invoice_number' => $invoiceNumber2,
            'invoice_type_code' => 'F1',
            'description' => 'Multiple products with global discount',
            'customer_party_name' => 'Cliente B S.L.',
            'customer_party_tax_id' => 'B87654321',
            'customer_party_country' => 'es',
            'customer_party_postalcode' => '20010',
            'customer_party_address' => 'Avenida Principal 45',
            'tax_inclusive_amount' => 217.8,  // After discount
            'tax_amount' => 37.8,              // Total tax
            'currency' => 'EUR',

            // Multiple lines including a global discount
            'tax_report_lines' => [
                // Line 1: Product A
                [
                    'quantity' => 1.0,
                    'description' => 'Product A',
                    'price' => 100.0,
                    'tax_inclusive_amount' => 121.0,
                    'tax_exclusive_amount' => 100.0,
                    'tax_amount' => 21.0
                ],
                // Line 2: Product B
                [
                    'quantity' => 2.0,
                    'description' => 'Product B',
                    'price' => 50.0,
                    'tax_inclusive_amount' => 121.0,
                    'tax_exclusive_amount' => 100.0,
                    'tax_amount' => 21.0
                ],
                // Line 3: Global discount (negative amounts)
                [
                    'quantity' => 1.0,
                    'description' => 'Global Discount 10%',
                    'price' => -20.0,
                    'tax_inclusive_amount' => -24.2,
                    'tax_exclusive_amount' => -20.0,
                    'tax_amount' => -4.2
                ]
            ],

            // Aggregated tax breakdown (sum of all lines)
            'tax_breakdowns' => [
                [
                    'category' => 'S',
                    'non_exempt' => true,
                    'non_exemption_code' => 'S1',
                    'percent' => 21.0,
                    'taxable_base' => 180.0,  // 100 + 100 - 20
                    'tax_amount' => 37.8       // 21 + 21 - 4.2
                ]
            ]
        ]
    ]);

    echo "Complex tax report created successfully!\n";
    echo "  ID: {$complexReport['id']}\n";
    echo "  Lines: " . count($complexReport['tax_report_lines']) . "\n";
    echo "  Total: €{$complexReport['tax_inclusive_amount']}\n\n";

    // ============================================
    // Step 5: Create an annullation (anulación)
    // ============================================
    echo "Creating an annullation...\n";
    echo "Note: This cancels the original tax report by sending an annullation to the Tax Authority\n";
    echo "Note: Corrections (Zuzendu) are NOT currently supported by B2BRouter\n\n";

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

    echo "=== TicketBAI Example Complete ===\n";

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
