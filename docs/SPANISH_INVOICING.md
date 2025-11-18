# Spanish Invoicing with Verifactu

Comprehensive guide for implementing Spanish electronic invoicing and tax reporting compliance using the B2BRouter PHP SDK.

## Table of Contents

- [Overview](#overview)
- [Spanish Legal Requirements](#spanish-legal-requirements)
- [What is Verifactu?](#what-is-verifactu)
- [How B2BRouter Simplifies Compliance](#how-b2brouter-simplifies-compliance)
- [Setup](#setup)
- [Creating Spanish Invoices](#creating-spanish-invoices)
- [Working with Tax Reports](#working-with-tax-reports)
- [Tax Report Lifecycle](#tax-report-lifecycle)
- [Invoice Line Taxes](#invoice-line-taxes)
- [Common Scenarios](#common-scenarios)
- [Troubleshooting](#troubleshooting)
- [Reference](#reference)

## Overview

Spain has implemented strict anti-fraud legislation requiring businesses to use certified invoicing systems that ensure data integrity and enable real-time tax reporting. The B2BRouter PHP SDK provides full compliance with these requirements.

**Important Prerequisites:**
- Only businesses subject to Spanish Verifactu obligations need to generate tax reports
- You must configure `TaxReportSettings` for your account before tax reports can be generated (see [Setup](#setup) section)
- Configuration can be done via the SDK or through the B2BRouter web interface

**Key Benefits:**
- Automatic Verifactu compliance for obligated issuers
- No need for your own qualified electronic certificate
- Automatic tax report generation and submission to AEAT (once configured)
- Built-in QR code generation for invoice verification
- Hash chain computation for tamper-proof audit trails
- Rate limiting and retry logic handled automatically

## Spanish Legal Requirements

### Anti-Fraud Law (Law 11/2021)

The [Spanish Anti-Fraud Law (Law 11/2021)](https://www.boe.es/diario_boe/txt.php?id=BOE-A-2021-11473) mandates that businesses ensure their invoicing and accounting systems meet strict technical standards to prevent data manipulation.

### Royal Decree 1007/2023

[Royal Decree 1007/2023](https://www.boe.es/buscar/act.php?id=BOE-A-2023-24840) defines the technical requirements for digital billing systems, emphasizing:
- Standardized invoice formats accessible by tax authorities
- Certified software to ensure data integrity
- Real-time reporting of invoices to tax authorities

## What is Verifactu?

Verifactu is Spain's compliance system for meeting the invoicing requirements outlined in the Anti-Fraud Law. It ensures that all invoices are securely transmitted to the Spanish Tax Authority (AEAT - Agencia Estatal de Administración Tributaria) for verification of integrity and authenticity.

### Verifactu Requirements

The system involves:
1. Creating tax report XML files
2. Computing digital fingerprints for each tax report
3. Creating hash chains for tamper-proof audit trails
4. Assembling "Libro de Registro" (Ledgers) with one or more tax reports
5. Authenticated submission to AEAT using qualified electronic certificates
6. Respecting rate limits (max 1 call per minute, unless submitting >1,000 invoices)
7. Generating QR codes for invoice verification
8. Processing responses from AEAT for each tax report

## How B2BRouter Simplifies Compliance

With B2BRouter, you can achieve full compliance by making a few REST API calls:

**What B2BRouter Handles for You:**
- Qualified electronic certificate (B2BRouter is a Social Collaborator in Tax Management)
- XML generation and validation
- Digital fingerprint computation
- Hash chain management
- Rate limiting and retries
- QR code generation
- Asynchronous submission to AEAT
- Response processing and error handling

**What You Need to Do:**
- Configure your account once
- Create invoices with proper tax structure
- Monitor tax report states (via webhooks or polling)

## Setup

### Step 1: Configure Tax Report Settings

Before creating invoices, configure Verifactu settings for your account:

```php
<?php

require_once 'vendor/autoload.php';

use B2BRouter\B2BRouterClient;

$client = new B2BRouterClient($_ENV['B2B_API_KEY']);
$accountId = $_ENV['B2B_ACCOUNT_ID'];

// Configure Verifactu for your account
$settings = $client->taxReportSettings->create($accountId, [
    'tax_report_setting' => [
        'code' => 'VeriFactu',
        'start_date' => '2025-01-01',
        'auto_generate' => true,
        'auto_send' => true,
        'reason_vat_exempt' => 'E1',
        'special_regime_key' => '01',
        'reason_no_subject' => 'N1',
        'credit_note_code' => 'R1'
    ]
]);
```

**Configuration Parameters:**
- `code`: Always 'VeriFactu' for Spanish compliance
- `start_date`: Date from which Verifactu reporting starts
- `auto_generate`: Automatically create tax reports (recommended: true)
- `auto_send`: Automatically submit to AEAT (recommended: true)
- `reason_vat_exempt`: Default exemption code (E1 = Article 20)
- `special_regime_key`: Default tax regime (01 = General regime)
- `reason_no_subject`: Default non-subject code (N1 = Not subject)
- `credit_note_code`: Default credit note type (R1 = Error in law)

### Step 2: Verify Configuration

```php
// Retrieve settings to verify
$settings = $client->taxReportSettings->retrieve($accountId, 'VeriFactu');
echo "Verifactu configured: {$settings['code']}\n";
echo "Auto-send enabled: " . ($settings['auto_send'] ? 'Yes' : 'No') . "\n";
```

## Creating Spanish Invoices

### Basic Spanish Invoice

```php
<?php

require_once 'vendor/autoload.php';

use B2BRouter\B2BRouterClient;

$client = new B2BRouterClient($_ENV['B2B_API_KEY']);
$accountId = $_ENV['B2B_ACCOUNT_ID'];

$invoice = $client->invoices->create($accountId, [
    'invoice' => [
        'number' => 'F-2025-001',
        'date' => date('Y-m-d'),
        'due_date' => date('Y-m-d', strtotime('+30 days')),
        'currency' => 'EUR',
        'language' => 'es',  // Spanish language
        'contact' => [
            'name' => 'Cliente Ejemplo SA',
            'tin_value' => 'ESB12345678',  // Spanish NIF/CIF
            'country' => 'ES',
            'address' => 'Calle Mayor, 1',
            'city' => 'Madrid',
            'postalcode' => '28001',
            'email' => 'facturacion@ejemplo.com',
        ],
        'invoice_lines_attributes' => [
            [
                'description' => 'Servicios profesionales',
                'quantity' => 1,
                'price' => 1000.00,
                'taxes_attributes' => [
                    [
                        'name' => 'IVA',
                        'category' => 'S',  // Standard rate
                        'percent' => 21.0,  // 21% IVA
                    ]
                ]
            ]
        ],
    ],
    'send_after_import' => true  // Send immediately and generate tax report
]);

echo "Invoice created: {$invoice['id']}\n";
echo "Tax Report ID: {$invoice['tax_report_ids'][0]}\n";
```

### Multi-Line Invoice with Different Tax Rates

```php
$invoice = $client->invoices->create($accountId, [
    'invoice' => [
        'number' => 'F-2025-002',
        'date' => date('Y-m-d'),
        'due_date' => date('Y-m-d', strtotime('+30 days')),
        'currency' => 'EUR',
        'language' => 'es',
        'contact' => [
            'name' => 'Restaurante Ejemplo SL',
            'tin_value' => 'ESB87654321',
            'country' => 'ES',
            'city' => 'Barcelona',
            'postalcode' => '08001',
            'email' => 'admin@restaurante.com',
        ],
        'invoice_lines_attributes' => [
            [
                'description' => 'Alimentos (IVA 10%)',
                'quantity' => 50,
                'price' => 20.00,
                'taxes_attributes' => [
                    [
                        'name' => 'IVA',
                        'category' => 'AA',  // Low rate
                        'percent' => 10.0,
                    ]
                ]
            ],
            [
                'description' => 'Bebidas (IVA 21%)',
                'quantity' => 30,
                'price' => 5.00,
                'taxes_attributes' => [
                    [
                        'name' => 'IVA',
                        'category' => 'S',  // Standard rate
                        'percent' => 21.0,
                    ]
                ]
            ],
            [
                'description' => 'Libros (IVA 4%)',
                'quantity' => 10,
                'price' => 15.00,
                'taxes_attributes' => [
                    [
                        'name' => 'IVA',
                        'category' => 'AAA',  // Super reduced rate
                        'percent' => 4.0,
                    ]
                ]
            ],
        ],
    ],
    'send_after_import' => true
]);
```

## Working with Tax Reports

### Retrieving Tax Reports

```php
// Get tax report from invoice
$taxReportId = $invoice['tax_report_ids'][0];
$taxReport = $client->taxReports->retrieve($taxReportId);

echo "Tax Report State: {$taxReport['state']}\n";
echo "Invoice Number: {$taxReport['invoice_number']}\n";
echo "Invoice Date: {$taxReport['invoice_date']}\n";

// QR code (base64 encoded PNG)
if (!empty($taxReport['qr'])) {
    echo "QR Code: Available\n";
    // Save QR code to file
    file_put_contents('qr_code.png', base64_decode($taxReport['qr']));
}

// Verification URL for customer
echo "Verification URL: {$taxReport['identifier']}\n";
```

### Listing Tax Reports

```php
// List all tax reports for an account
$taxReports = $client->taxReports->all($accountId, [
    'limit' => 50,
    'offset' => 0,
    'updated_at_from' => '2025-01-01',
]);

foreach ($taxReports as $report) {
    echo "Tax Report {$report['id']}: {$report['state']}\n";
}

// Filter by specific invoice
$invoiceTaxReports = $client->taxReports->all($accountId, [
    'invoice_id' => $invoiceId,
]);
```

## Tax Report Lifecycle

Tax reports are processed asynchronously and go through several states:

### States

1. **processing** - Initial state. The tax report is being chained and prepared for submission
2. **registered** - Successfully submitted to AEAT and accepted
3. **error** - Submission failed (see error details in response)
4. **registered_with_errors** - Submitted but AEAT reported warnings
5. **annulled** - Tax report has been cancelled

### Monitoring Options

#### Option 1: Webhooks (Recommended)

Set up a webhook endpoint to receive real-time notifications:

```php
// webhook_handler.php
<?php

$payload = json_decode(file_get_contents('php://input'), true);

if ($payload['event'] === 'tax_report.state_changed') {
    $taxReportId = $payload['data']['id'];
    $state = $payload['data']['state'];
    $invoiceId = $payload['data']['invoice_id'];

    // Check if final state reached
    if (in_array($state, ['registered', 'error', 'registered_with_errors', 'annulled'])) {
        // Log or process the final state
        error_log("Tax Report {$taxReportId} for Invoice {$invoiceId} is now {$state}");

        // Take appropriate action
        if ($state === 'registered') {
            // Success - invoice is compliant
            updateInvoiceStatus($invoiceId, 'compliant');
        } elseif ($state === 'error') {
            // Error - handle failure
            notifyAdmin("Tax report {$taxReportId} failed submission");
        }
    }
}

http_response_code(200);
```

**Configure webhook in B2BRouter dashboard:**
1. Go to Settings > Webhooks
2. Add your endpoint URL
3. Select `tax_report.state_changed` event
4. Save configuration

#### Option 2: Polling

Periodically check the tax report state:

```php
function waitForTaxReportCompletion($client, $taxReportId, $maxAttempts = 60, $sleepSeconds = 5) {
    $attempts = 0;

    while ($attempts < $maxAttempts) {
        $taxReport = $client->taxReports->retrieve($taxReportId);
        $state = $taxReport['state'];

        echo "Attempt {$attempts}: State is {$state}\n";

        // Check for final states
        if (in_array($state, ['registered', 'error', 'registered_with_errors', 'annulled'])) {
            return [
                'success' => in_array($state, ['registered', 'registered_with_errors']),
                'state' => $state,
                'tax_report' => $taxReport
            ];
        }

        sleep($sleepSeconds);
        $attempts++;
    }

    throw new Exception("Tax report processing timeout after {$maxAttempts} attempts");
}

// Usage
try {
    $result = waitForTaxReportCompletion($client, $taxReportId);

    if ($result['success']) {
        echo "Tax report successfully registered!\n";
        echo "QR Code available: " . (!empty($result['tax_report']['qr']) ? 'Yes' : 'No') . "\n";
    } else {
        echo "Tax report failed: {$result['state']}\n";
    }
} catch (Exception $e) {
    echo "Error: {$e->getMessage()}\n";
}
```

## Invoice Line Taxes

### Standard IVA Rates in Spain

```php
'taxes_attributes' => [
    [
        'name' => 'IVA',
        'category' => 'S',      // Standard rate
        'percent' => 21.0,
    ]
]
```

### Reduced IVA Rates

```php
// 10% reduced rate (food, transport, hospitality)
'taxes_attributes' => [
    [
        'name' => 'IVA',
        'category' => 'AA',     // Low rate
        'percent' => 10.0,
    ]
]

// 4% super-reduced rate (basic necessities, books, medicines)
'taxes_attributes' => [
    [
        'name' => 'IVA',
        'category' => 'AAA',    // Super reduced rate
        'percent' => 4.0,
    ]
]
```

### Exempt Operations

```php
'taxes_attributes' => [
    [
        'name' => 'IVA',
        'category' => 'E',      // Exempt
        'percent' => 0.0,
    ]
]
```

### Reverse Charge (Inversión del Sujeto Pasivo)

```php
'taxes_attributes' => [
    [
        'name' => 'IVA',
        'category' => 'AE',     // Reverse charge
        'percent' => 0.0,
    ]
]
```

### Tax Categories Reference

| Code | Description | Typical Rate |
|------|-------------|--------------|
| S | Standard rate | 21% |
| H | High rate (same as standard for Spain) | 21% |
| AA | Low rate (reduced) | 10% |
| AAA | Super low rate (super-reduced) | 4% |
| E | Exempt | 0% |
| Z | Zero rated | 0% |
| AE | Reverse charge | 0% |
| NS | Not subject | 0% |
| G | Free export item, VAT not charged | 0% |
| O | Services outside scope of tax | 0% |
| K | VAT exempt for EEA intra-community supply | 0% |

## Common Scenarios

### Scenario 1: Standard B2B Invoice

```php
$invoice = $client->invoices->create($accountId, [
    'invoice' => [
        'number' => 'F-2025-100',
        'date' => date('Y-m-d'),
        'due_date' => date('Y-m-d', strtotime('+30 days')),
        'currency' => 'EUR',
        'language' => 'es',
        'payment_method' => 2,  // Bank transfer
        'contact' => [
            'name' => 'Empresa Cliente SL',
            'tin_value' => 'ESB12345678',
            'country' => 'ES',
            'email' => 'contabilidad@cliente.es',
        ],
        'invoice_lines_attributes' => [
            [
                'description' => 'Consultoría estratégica',
                'quantity' => 40,
                'price' => 125.00,
                'taxes_attributes' => [
                    ['name' => 'IVA', 'category' => 'S', 'percent' => 21.0]
                ]
            ]
        ],
    ],
    'send_after_import' => true
]);
```

### Scenario 2: Simplified Invoice (Factura Simplificada)

For sales under €400 where customer identification is not required:

```php
$invoice = $client->invoices->create($accountId, [
    'invoice' => [
        'type' => 'IssuedSimplifiedInvoice',
        'number' => 'FS-2025-001',
        'date' => date('Y-m-d'),
        'currency' => 'EUR',
        'language' => 'es',
        'contact' => [
            'name' => 'Cliente final',
            'country' => 'ES',
        ],
        'invoice_lines_attributes' => [
            [
                'description' => 'Producto',
                'quantity' => 2,
                'price' => 50.00,
                'taxes_attributes' => [
                    ['name' => 'IVA', 'category' => 'S', 'percent' => 21.0]
                ]
            ]
        ],
    ],
    'send_after_import' => true
]);
```

### Scenario 3: Credit Note (Factura Rectificativa)

```php
$creditNote = $client->invoices->create($accountId, [
    'invoice' => [
        'number' => 'FR-2025-001',
        'date' => date('Y-m-d'),
        'currency' => 'EUR',
        'language' => 'es',
        'is_credit_note' => true,
        'amended_number' => 'F-2025-100',  // Original invoice number
        'amended_date' => '2025-01-15',     // Original invoice date
        'amend_reason' => 'R1',             // Error founded in law
        'contact' => [
            'name' => 'Empresa Cliente SL',
            'tin_value' => 'ESB12345678',
            'country' => 'ES',
            'email' => 'contabilidad@cliente.es',
        ],
        'invoice_lines_attributes' => [
            [
                'description' => 'Corrección de factura F-2025-100',
                'quantity' => 1,
                'price' => -100.00,  // Negative amount
                'taxes_attributes' => [
                    ['name' => 'IVA', 'category' => 'S', 'percent' => 21.0]
                ]
            ]
        ],
    ],
    'send_after_import' => true
]);
```

### Scenario 4: Intra-Community Supply

For B2B sales to other EU countries:

```php
$invoice = $client->invoices->create($accountId, [
    'invoice' => [
        'number' => 'F-EU-2025-001',
        'date' => date('Y-m-d'),
        'due_date' => date('Y-m-d', strtotime('+30 days')),
        'currency' => 'EUR',
        'language' => 'en',
        'contact' => [
            'name' => 'European Company GmbH',
            'tin_value' => 'DE123456789',  // German VAT ID
            'tin_scheme' => '02',          // EU VAT ID
            'country' => 'DE',
            'email' => 'accounting@company.de',
        ],
        'invoice_lines_attributes' => [
            [
                'description' => 'Professional Services',
                'quantity' => 1,
                'price' => 5000.00,
                'taxes_attributes' => [
                    [
                        'name' => 'IVA',
                        'category' => 'K',  // Intra-community exempt
                        'percent' => 0.0,
                    ]
                ]
            ]
        ],
    ],
    'send_after_import' => true
]);
```

## Reference

### Important Field Names

- `tin_value` - Tax Identification Number (NIF/CIF for Spain)
- `invoice_lines_attributes` - Array of invoice lines
- `taxes_attributes` - Array of taxes for each line
- `tax_report_ids` - Array of associated tax report IDs
- `send_after_import` - Send invoice and generate tax report immediately

### Spanish Tax Identification Schemes

- **9920** - Spanish NIF/CIF (default for ES)
- **02** - EU VAT ID (for EU customers)
- **03** - Passport
- **04** - Official ID from country of residence
- **06** - Other supporting document

### Invoice Type Codes

- **F1** - Standard invoice
- **F2** - Simplified invoice
- **F3** - Replacement for simplified invoices
- **R1** - Credit note (error founded in law)
- **R2** - Credit note (Article 80.3)
- **R3** - Credit note (Article 80.4)
- **R4** - Credit note (other reasons)
- **R5** - Credit note for simplified invoices

### Special Regime Keys

- **01** - General regime (most common)
- **02** - Export
- **07** - Cash accounting regime
- **08** - Canary Islands (IGIC)
- **14** - Invoice with VAT pending (public works)

### Resources

- [B2BRouter API Documentation](https://developer.b2brouter.net)
- [B2Brouter Verifactu Guide](https://developer.b2brouter.net/v2025-10-13/docs/verifactu) - Full B2Brouter Verifactu Guide.
- [Verifactu Official Specifications (AEAT)](https://www.agenciatributaria.es)
- [Spanish Anti-Fraud Law](https://www.boe.es/diario_boe/txt.php?id=BOE-A-2021-11473)
- [Royal Decree 1007/2023](https://www.boe.es/buscar/act.php?id=BOE-A-2023-24840)
