<?php

require_once __DIR__ . '/bootstrap.php';

use B2BRouter\B2BRouterClient;

$client = new B2BRouterClient($_ENV['B2B_API_KEY'] ?? 'your-api-key');
$invoiceId = $argv[1] ?? null;

if (!$invoiceId) {
    echo "Usage: php update_invoice.php <invoice_id>\n";
    exit(1);
}

try {
    // Retrieve existing invoice
    echo "Fetching invoice {$invoiceId}...\n";
    $invoice = $client->invoices->retrieve($invoiceId);

    echo "Current status: {$invoice['state']}\n";
    echo "Current extra info: " . ($invoice['extra_info'] ?? 'None') . "\n";
    echo "Current file reference: " . ($invoice['file_reference'] ?? 'None') . "\n\n";

    // Update invoice
    echo "Updating invoice...\n";
    $updated = $client->invoices->update($invoiceId, [
        'invoice' => [
            'extra_info' => 'Updated on ' . date('Y-m-d H:i:s') . ' - Payment reminder sent',
            'file_reference' => 'foo123',
        ]
    ]);

    echo "✓ Invoice updated successfully!\n";
    echo "  New extra info: {$updated['extra_info']}\n";
    echo "  New file reference: {$updated['file_reference']}\n";

} catch (Exception $e) {
    echo "✗ Error: {$e->getMessage()}\n";
    exit(1);
}
