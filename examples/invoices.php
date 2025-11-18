<?php

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
    echo "=== B2BRouter Invoice Examples ===\n\n";

    // ============================================
    // 1. Create an invoice
    // ============================================
    echo "1. Creating an invoice...\n";
    $invoice = $client->invoices->create($accountId, [
        'invoice' => [
            'number' => 'INV-2025-001',
            'date' => '2025-01-15',
            'due_date' => '2025-02-15',
            'currency' => 'EUR',
            'contact' => [
                'name' => 'Acme Corporation',
                'tin_value' => 'ESP9109010J',
                'country' => 'ES',
            ],
            'invoice_lines_attributes' => [
                [
                    'description' => 'Professional Services',
                    'quantity' => 10,
                    'price' => 100.00,
                    'taxes_attributes' => [
                        [
                            'name' => 'IVA',
                            'category' => 'S',
                            'percent' => 21.0,
                        ]
                    ]
                ]
            ]
        ],
        'send_after_import' => false
    ]);

    echo "Invoice created with ID: {$invoice['id']}\n\n";

    // ============================================
    // 2. Retrieve an invoice
    // ============================================
    echo "2. Retrieving the invoice...\n";
    $invoiceId = $invoice['id'];
    $retrievedInvoice = $client->invoices->retrieve($invoiceId);
    echo "Retrieved invoice number: {$retrievedInvoice['number']}\n\n";

    // ============================================
    // 3. Update an invoice
    // ============================================
    echo "3. Updating the invoice...\n";
    $updatedInvoice = $client->invoices->update($invoiceId, [
        'invoice' => [
            'extra_info' => 'Payment terms: 30 days net'
        ]
    ]);
    echo "Invoice updated successfully\n\n";

    // ============================================
    // 4. List invoices with pagination
    // ============================================
    echo "4. Listing invoices...\n";
    $invoices = $client->invoices->all($accountId, [
        'limit' => 10,
        'offset' => 0,
        'date_from' => '2025-01-01',
    ]);

    echo "Found {$invoices->count()} invoices (Total: {$invoices->getTotal()})\n";
    foreach ($invoices as $inv) {
        echo "  - Invoice {$inv['number']}: €{$inv['total']} {$inv['currency']}\n";
    }

    if ($invoices->hasMore()) {
        echo "There are more invoices available\n";
    }
    echo "\n";

    // ============================================
    // 5. Paginate through all invoices
    // ============================================
    echo "5. Paginating through invoices...\n";
    $offset = 0;
    $limit = 25;
    $allInvoices = [];

    do {
        $page = $client->invoices->all($accountId, [
            'limit' => $limit,
            'offset' => $offset,
        ]);

        foreach ($page as $inv) {
            $allInvoices[] = $inv;
        }

        $offset += $limit;
    } while ($page->hasMore());

    echo "Fetched all " . count($allInvoices) . " invoices\n\n";

    // ============================================
    // 6. Validate an invoice
    // ============================================
    echo "6. Validating the invoice...\n";
    $validation = $client->invoices->validate($invoiceId);
    echo "Validation result: " . ($validation['valid'] ?? 'OK') . "\n\n";

    // ============================================
    // 7. Send an invoice
    // ============================================
    echo "7. Sending the invoice...\n";
    $sendResult = $client->invoices->send($invoiceId);
    echo "Invoice sent successfully\n\n";

    // ============================================
    // 8. Mark invoice status
    // ============================================
    echo "8. Marking invoice as sent...\n";
    $markedInvoice = $client->invoices->markAs($invoiceId, [
        'state' => 'sent'
    ]);
    echo "Invoice marked as sent\n\n";

    // ============================================
    // 9. Delete an invoice
    // ============================================
    echo "9. Deleting the invoice...\n";
    $deletedInvoice = $client->invoices->delete($invoiceId);
    echo "Invoice deleted successfully\n\n";

    echo "=== All operations completed successfully ===\n";

} catch (ApiErrorException $e) {
    echo "API Error: {$e->getMessage()}\n";
    echo "HTTP Status: {$e->getHttpStatus()}\n";
    echo "Request ID: {$e->getRequestId()}\n";

    if ($e->getJsonBody()) {
        echo "Error details: " . json_encode($e->getJsonBody(), JSON_PRETTY_PRINT) . "\n";
    }
} catch (\Exception $e) {
    echo "Error: {$e->getMessage()}\n";
}
