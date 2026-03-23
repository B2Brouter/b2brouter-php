<?php

require_once __DIR__ . '/bootstrap.php';

use B2BRouter\B2BRouterClient;

$client = new B2BRouterClient(env('B2B_API_KEY'), [
    'api_version' => env('B2B_API_VERSION', '2025-10-13'),
    'api_base' => env('B2B_API_BASE'),
]);

$accountId = env('B2B_ACCOUNT_ID');

// Define invoice line items
$items = [
    [
        'description' => 'Web Development - 40 hours',
        'quantity' => 40,
        'price' => 75.00,
        'tax_rate' => 21.0,
    ],
    [
        'description' => 'UI/UX Design - 20 hours',
        'quantity' => 20,
        'price' => 85.00,
        'tax_rate' => 21.0,
    ],
    [
        'description' => 'Project Management - 10 hours',
        'quantity' => 10,
        'price' => 95.00,
        'tax_rate' => 21.0,
    ],
];

// Build invoice lines
$lines = [];

foreach ($items as $item) {
    $lines[] = [
        'description' => $item['description'],
        'quantity' => $item['quantity'],
        'price' => $item['price'],
        'taxes_attributes' => [
            [
                'name' => 'IVA',
                'category' => 'S',
                'percent' => $item['tax_rate'],
            ]
        ]
    ];
}

try {
    $invoice = $client->invoices->create($accountId, [
        'invoice' => [
            'number' => 'INV-2025-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
            'date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+30 days')),
            'currency' => 'EUR',
            'contact' => [
                'name' => 'Tech Startup Inc',
                'tin_value' => 'ESP9109010J',
                'country' => 'ES',
                'email' => 'accounts@techstartup.com',
            ],
            'invoice_lines_attributes' => $lines,
            'extra_info' => 'Payment terms: 30 days net. Bank transfer preferred.',
        ]
    ]);

    echo "✓ Detailed invoice created!\n";
    echo "  Invoice: {$invoice['number']}\n";
    echo "  Items: " . count($lines) . "\n";
    echo "  Subtotal: €" . number_format($invoice['subtotal'], 2) . "\n";

    $totalTax = 0;
    foreach ($invoice['taxes'] as $tax) {
        $totalTax += $tax['amount'];
    }
    echo "  Tax (21%): €" . number_format($totalTax, 2) . "\n";
    echo "  Total: €" . number_format($invoice['total'], 2) . "\n";
    echo "  State: {$invoice['state']}\n";

} catch (Exception $e) {
    echo "✗ Error: {$e->getMessage()}\n";
    exit(1);
}
