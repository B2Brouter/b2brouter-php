<?php

require_once __DIR__ . '/bootstrap.php';

use B2BRouter\B2BRouterClient;
use B2BRouter\Exception\ApiErrorException;

// Check required environment variables
checkRequiredEnv();

/**
 * Example: Invoicing in Spain with Verifactu (Tax Report)
 *
 * This example demonstrates how to:
 * 1. Create an invoice for a Spanish customer with proper IVA (VAT) taxes
 * 2. Send the invoice automatically after creation
 * 3. View the complete invoice response payload
 * 4. Retrieve the associated tax report (Verifactu)
 * 5. View the tax report response payload
 *
 * This is particularly useful for Spanish businesses that need to comply
 * with Verifactu requirements and track their tax reporting.
 */

// Initialize client
$client = new B2BRouterClient(env('B2B_API_KEY'), [
    'api_version' => env('B2B_API_VERSION', '2025-10-13'),
    'api_base' => env('B2B_API_BASE'),
]);

$accountId = env('B2B_ACCOUNT_ID');

try {
    echo "=== Spanish Invoice with Verifactu Example ===\n\n";

    // ============================================
    // 1. Create and send an invoice
    // ============================================
    echo "Step 1: Creating invoice for Spanish customer...\n\n";

    $invoice = $client->invoices->create($accountId, [
        'invoice' => [
            'number' => 'INV-ES-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
            'date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+30 days')),
            'currency' => 'EUR',
            'language' => 'es',
            'contact' => [
                'name' => 'Cliente Ejemplo SA',
                'tin_value' => 'ESP9109010J',
                'country' => 'ES',
                'address' => 'Calle Gran Vía, 123',
                'city' => 'Madrid',
                'postalcode' => '28013',
                'email' => 'facturacion@ejemplo.com',
            ],
            'invoice_lines_attributes' => [
                [
                    'description' => 'Servicios de consultoría - Enero 2025',
                    'quantity' => 10,
                    'price' => 150.00,
                    'taxes_attributes' => [
                        [
                            'name' => 'IVA',
                            'category' => 'S',  // S = Standard rate
                            'percent' => 21.0,
                        ]
                    ]
                ],
                [
                    'description' => 'Soporte técnico - Enero 2025',
                    'quantity' => 5,
                    'price' => 200.00,
                    'taxes_attributes' => [
                        [
                            'name' => 'IVA',
                            'category' => 'S',
                            'percent' => 21.0,
                        ]
                    ]
                ],
            ],
            'extra_info' => 'Condiciones de pago: 30 días. Transferencia bancaria.',
        ],
        'send_after_import' => true  // Automatically send after creation
    ]);

    echo "✓ Invoice created and sent successfully!\n\n";

    // ============================================
    // 2. Display invoice response payload
    // ============================================
    echo "=== INVOICE CREATION RESPONSE ===\n";
    echo json_encode(['invoice' => $invoice], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n\n";

    // ============================================
    // 3. Display invoice summary
    // ============================================
    echo "--- Invoice Summary ---\n";
    echo "  Invoice ID: {$invoice['id']}\n";
    echo "  Invoice Number: {$invoice['number']}\n";
    echo "  Date: {$invoice['date']}\n";
    echo "  Due Date: {$invoice['due_date']}\n";
    echo "  State: {$invoice['state']}\n";
    echo "  Customer: {$invoice['contact']['name']}\n";
    echo "  Customer Email: {$invoice['contact']['email']}\n";
    echo "  Language: {$invoice['language']}\n\n";

    echo "  Financial Summary:\n";
    echo "    Subtotal: €" . number_format($invoice['subtotal'], 2) . "\n";

    if (!empty($invoice['taxes'])) {
        foreach ($invoice['taxes'] as $tax) {
            echo "    {$tax['name']}: €" . number_format($tax['amount'], 2) .
                 " ({$tax['percent']}% on €" . number_format($tax['base'], 2) . ")\n";
        }
    }

    echo "    Total: €" . number_format($invoice['total'], 2) . "\n\n";

    // ============================================
    // 4. Retrieve tax report (Verifactu)
    // ============================================
    if (!empty($invoice['tax_report_ids']) && count($invoice['tax_report_ids']) > 0) {
        $taxReportId = $invoice['tax_report_ids'][0];

        echo "Step 2: Retrieving Tax Report (Verifactu)...\n";
        echo "  Tax Report ID: {$taxReportId}\n\n";

        $taxReport = $client->taxReports->retrieve($taxReportId);

        echo "✓ Tax Report retrieved successfully!\n\n";

        // ============================================
        // 5. Display tax report response payload
        // ============================================
        echo "=== TAX REPORT RESPONSE ===\n";
        echo json_encode(['tax_report' => $taxReport], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n\n";

        // ============================================
        // 6. Display tax report summary
        // ============================================
        echo "--- Tax Report Summary ---\n";
        echo "  Tax Report ID: {$taxReport['id']}\n";

        if (isset($taxReport['type'])) {
            echo "  Type: {$taxReport['type']}\n";
        }

        if (isset($taxReport['period_start']) && isset($taxReport['period_end'])) {
            echo "  Period: {$taxReport['period_start']} to {$taxReport['period_end']}\n";
        }

        if (isset($taxReport['state'])) {
            echo "  State: {$taxReport['state']}\n";
        }

        if (isset($taxReport['created_at'])) {
            echo "  Created At: {$taxReport['created_at']}\n";
        }

        echo "\n";

    } else {
        echo "⚠ No tax report IDs found in invoice response\n";
        echo "  This may be because:\n";
        echo "  - Tax reporting is not configured for this account\n";
        echo "  - The invoice doesn't require a tax report\n";
        echo "  - Tax reports are generated asynchronously\n\n";
    }

    echo "=== Example completed successfully ===\n\n";

    echo "Notes:\n";
    echo "- This invoice uses Spanish IVA (VAT) at the standard rate of 21%\n";
    echo "- The invoice is automatically sent to the customer's email\n";
    echo "- Tax reports (Verifactu) are automatically generated for Spanish invoices\n";
    echo "- The tax report can be used for compliance and reporting purposes\n";

} catch (ApiErrorException $e) {
    echo "✗ API Error occurred:\n";
    echo "  Message: {$e->getMessage()}\n";
    echo "  Status: {$e->getHttpStatus()}\n";
    echo "  Request ID: {$e->getRequestId()}\n";

    if ($e->getJsonBody()) {
        echo "  Details: " . json_encode($e->getJsonBody(), JSON_PRETTY_PRINT) . "\n";
    }

    exit(1);
} catch (Exception $e) {
    echo "✗ Error: {$e->getMessage()}\n";
    exit(1);
}
