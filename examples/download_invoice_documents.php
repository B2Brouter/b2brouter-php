<?php
/**
 * Example: Create Invoice and Download Documents
 *
 * This example demonstrates:
 * 1. Creating a simple invoice
 * 2. Downloading the invoice as PDF
 * 3. Downloading the invoice as UBL BIS3 XML
 *
 * The downloaded files will be saved in the examples/output/ directory.
 */

require_once __DIR__ . '/bootstrap.php';

use B2BRouter\B2BRouterClient;
use B2BRouter\Exception\ApiErrorException;
use B2BRouter\Exception\ResourceNotFoundException;

// Display example header
exampleHeader(
    'Create Invoice and Download Documents',
    'Create an invoice and download it in PDF and UBL BIS3 formats'
);

// Check required environment variables
checkRequiredEnv();

// Initialize client
$client = new B2BRouterClient(env('B2B_API_KEY'), [
    'api_version' => env('B2B_API_VERSION', '2025-10-13'),
    'api_base' => env('B2B_API_BASE'),
]);

$accountId = env('B2B_ACCOUNT_ID');

// Create output directory if it doesn't exist
$outputDir = __DIR__ . '/output';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

try {
    // Step 1: Create an invoice
    echo "Step 1: Creating invoice...\n";

    $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

    $invoice = $client->invoices->create($accountId, [
        'invoice' => [
            'number' => $invoiceNumber,
            'date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+30 days')),
            'currency' => 'EUR',
            'contact' => [
                'name' => 'Acme Corporation',
                'tin_value' => 'ESP9109010J',
                'country' => 'ES',
                'address' => 'Calle Mayor 1',
                'city' => 'Madrid',
                'postalcode' => '28001',
                'email' => 'billing@acme.com',
            ],
            'invoice_lines_attributes' => [
                [
                    'description' => 'Professional Consulting Services',
                    'quantity' => 10,
                    'price' => 150.00,
                    'taxes_attributes' => [
                        [
                            'name' => 'IVA',
                            'category' => 'S',
                            'percent' => 21.0,
                        ]
                    ]
                ],
                [
                    'description' => 'Technical Support Hours',
                    'quantity' => 5,
                    'price' => 80.00,
                    'taxes_attributes' => [
                        [
                            'name' => 'IVA',
                            'category' => 'S',
                            'percent' => 21.0,
                        ]
                    ]
                ]
            ],
        ],
        'send_after_import' => false
    ]);

    echo "✓ Invoice created successfully!\n";
    echo "  ID: {$invoice['id']}\n";
    echo "  Number: {$invoice['number']}\n";
    echo "  Subtotal: €" . number_format($invoice['subtotal'], 2) . "\n";
    echo "  Total: €" . number_format($invoice['total'], 2) . " {$invoice['currency']}\n";
    echo "  State: {$invoice['state']}\n\n";

    $invoiceId = $invoice['id'];

    // Step 2: Download invoice as PDF
    echo "Step 2: Downloading invoice as PDF...\n";

    try {
        $pdfData = $client->invoices->downloadPdf($invoiceId);

        $pdfFilename = $outputDir . '/' . $invoice['number'] . '.pdf';
        file_put_contents($pdfFilename, $pdfData);

        $pdfSize = strlen($pdfData);
        echo "✓ PDF downloaded successfully!\n";
        echo "  File: {$pdfFilename}\n";
        echo "  Size: " . number_format($pdfSize) . " bytes\n";
        echo "  Format: PDF (pdf.invoice)\n\n";

    } catch (ResourceNotFoundException $e) {
        echo "✗ PDF not available: {$e->getMessage()}\n";
        echo "  Note: The invoice may need to be sent before PDF is available\n\n";
    }

    // Step 3: Download invoice as UBL BIS3 XML
    echo "Step 3: Downloading invoice as UBL BIS3 XML...\n";

    try {
        $ublData = $client->invoices->downloadAs($invoiceId, 'xml.ubl.invoice.bis3');

        $ublFilename = $outputDir . '/' . $invoice['number'] . '-ubl.xml';
        file_put_contents($ublFilename, $ublData);

        $ublSize = strlen($ublData);
        echo "✓ UBL BIS3 XML downloaded successfully!\n";
        echo "  File: {$ublFilename}\n";
        echo "  Size: " . number_format($ublSize) . " bytes\n";
        echo "  Format: UBL BIS3 (xml.ubl.invoice.bis3)\n\n";

        // Show a preview of the XML structure
        $xml = simplexml_load_string($ublData);
        if ($xml) {
            echo "  XML Preview:\n";
            echo "    Root Element: {$xml->getName()}\n";
            if (isset($xml->ID)) {
                echo "    Invoice ID: {$xml->ID}\n";
            }
            if (isset($xml->IssueDate)) {
                echo "    Issue Date: {$xml->IssueDate}\n";
            }
        }
        echo "\n";

    } catch (ResourceNotFoundException $e) {
        echo "✗ UBL BIS3 not available: {$e->getMessage()}\n";
        echo "  Note: UBL format may not be configured for this account\n\n";
    }

    // Step 4: Optional - Download as Facturae (Spanish format)
    echo "Step 4: Attempting to download as Facturae (Spanish format)...\n";

    try {
        $facturaeData = $client->invoices->downloadAs($invoiceId, 'xml.facturae.3.2.2');

        $facturaeFilename = $outputDir . '/' . $invoice['number'] . '-facturae.xml';
        file_put_contents($facturaeFilename, $facturaeData);

        $facturaeSize = strlen($facturaeData);
        echo "✓ Facturae XML downloaded successfully!\n";
        echo "  File: {$facturaeFilename}\n";
        echo "  Size: " . number_format($facturaeSize) . " bytes\n";
        echo "  Format: Facturae 3.2.2 (xml.facturae.3.2.2)\n\n";

    } catch (ResourceNotFoundException $e) {
        echo "ℹ Facturae not available (expected for non-Spanish invoices)\n\n";
    }

    // Summary
    echo str_repeat('-', 60) . "\n";
    echo "Summary:\n";
    echo "  Invoice: {$invoice['number']} (ID: {$invoiceId})\n";
    echo "  Total Amount: €" . number_format($invoice['total'], 2) . "\n";
    echo "  Downloads saved to: {$outputDir}/\n";
    echo "\n";
    echo "Next steps:\n";
    echo "  - View the PDF: open {$outputDir}/{$invoice['number']}.pdf\n";
    echo "  - View the XML files in your editor\n";
    echo "  - Integrate into your application\n";
    echo "\n";

} catch (ApiErrorException $e) {
    echo "✗ Error: {$e->getMessage()}\n";
    echo "  Status: {$e->getHttpStatus()}\n";

    if ($e->getJsonBody()) {
        echo "  Details:\n";
        $details = json_encode($e->getJsonBody(), JSON_PRETTY_PRINT);
        echo "  " . str_replace("\n", "\n  ", $details) . "\n";
    }

    exit(1);
}
