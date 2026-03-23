# B2BRouter PHP SDK

Official PHP SDK for the B2BRouter API - Electronic Invoicing and Tax Reporting

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-8892BF.svg)](https://php.net/)

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Configuration](#configuration)
- [Core Concepts](#core-concepts)
  - [Invoice Operations](#invoice-operations)
  - [Tax Reports](#tax-reports)
  - [Spanish Invoicing with Verifactu](#spanish-invoicing-with-verifactu)
- [Pagination](#pagination)
- [Error Handling](#error-handling)
- [Examples](#examples)
- [Development](#development)
- [Documentation](#documentation)
- [Support](#support)
- [License](#license)

## Features

### Core Functionality
- **Complete invoice management** - Create, retrieve, update, delete, import, validate, send, and acknowledge invoices
- **Multi-format document downloads** - Export invoices as PDF, Spanish Facturae XML, UBL BIS3, and other formats
- **Tax report operations** - Create, retrieve, list, download, update (corrections), and delete (annullations) tax reports
- **Tax report settings** - Configure and manage Verifactu, TicketBAI, and other tax authority settings

### Developer Experience
- **Simple and intuitive API** - Clean, modern PHP interface with service-based architecture
- **Zero dependencies** - Runs on PHP 7.4+ with only standard extensions (cURL, JSON, mbstring)
- **Automatic authentication** - Secure API key management built-in
- **Pagination support** - Iterator and Countable implementations for easy traversal of large result sets
- **Comprehensive error handling** - Detailed exception hierarchy with HTTP status codes and request IDs
- **Automatic retries** - Network failures handled gracefully with exponential backoff (configurable)
- **Type safety** - Full PHP 7.4+ support with proper type hints

### Compliance and Tax Reporting
- **Spanish Verifactu** - Full compliance with Spanish Anti-Fraud Law (Law 11/2021) and Royal Decree 1007/2023
- **TicketBAI** - Basque Country invoicing compliance with automatic submission
- **QR code generation** - Automatic QR codes for invoice verification (embedded in tax reports)
- **Hash chain management** - Tamper-proof audit trails computed and maintained automatically
- **Automated AEAT submission** - Real-time tax report submission to Spanish Tax Authority with rate limiting
- **Multi-jurisdiction support** - Architecture supports Italian SDI, Polish KSeF, Saudi Zatca, and more

## Requirements

- PHP 7.4 or higher
- cURL extension
- JSON extension
- mbstring extension

## Installation

Install via Composer:

```bash
composer require b2brouter/b2brouter-php
```

## Quick Start

```php
<?php

require_once 'vendor/autoload.php';

use B2BRouter\B2BRouterClient;

// Initialize the client
$client = new B2BRouterClient('your-api-key-here');
$accountId = 'your-account-id';

// Create an invoice with proper tax structure
$invoice = $client->invoices->create($accountId, [
    'invoice' => [
        'number' => 'INV-2025-001',
        'date' => '2025-01-15',
        'due_date' => '2025-02-15',
        'currency' => 'EUR',
        'contact' => [
            'name' => 'Acme Corporation',
            'tin_value' => 'ESB12345678',
            'country' => 'ES',
            'email' => 'billing@acme.com',
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
    ]
]);

echo "Invoice created: {$invoice['id']}\n";
echo "Total: €{$invoice['total']}\n";
```

## Running Examples

The SDK includes comprehensive examples demonstrating all features. To run them:

### 1. Setup Environment

```bash
# Copy the example environment file
cp .env.example .env

# Edit .env and add your credentials
# Get your API key from: https://app.b2brouter.net
vim .env
```

Your `.env` file should look like:
```env
B2B_API_KEY=your-api-key-here
B2B_ACCOUNT_ID=your-account-id
# B2B_API_BASE=https://api.b2brouter.net  # Uncomment for production (defaults to staging)
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Run Examples

```bash
# Invoice examples
php examples/create_simple_invoice.php
php examples/download_invoice_documents.php
php examples/list_invoices.php
php examples/invoices.php

# Tax report examples (VeriFactu, TicketBAI)
php examples/tax_reports.php
php examples/verifactu_tax_report.php
php examples/ticketbai_tax_report.php

# See all available examples
ls examples/
```

All examples use the environment variables from your `.env` file automatically.

## Configuration

The client accepts several configuration options:

```php
$client = new B2BRouterClient('your-api-key', [
    // 'api_base' => 'https://api.b2brouter.net',  // Production URL
    // 'api_base' => 'https://api-staging.b2brouter.net',  // Staging URL (default)
    'api_version' => '2025-10-13',              // API version
    'timeout' => 80,                             // Request timeout in seconds
    'max_retries' => 3,                          // Maximum retry attempts
]);
```

**Default Environment:** The SDK defaults to the **staging environment** (`https://api-staging.b2brouter.net`) for safe testing. To use production, set `api_base` to `https://api.b2brouter.net`.

## Core Concepts

### Invoice Operations

#### Create an Invoice

```php
$invoice = $client->invoices->create($accountId, [
    'invoice' => [
        'number' => 'INV-2025-001',
        'date' => '2025-01-15',
        'due_date' => '2025-02-15',
        'currency' => 'EUR',
        'contact' => [
            'name' => 'Customer Name',
            'tin_value' => 'ESB12345678',
            'country' => 'ES',
            'email' => 'customer@example.com',
        ],
        'invoice_lines_attributes' => [
            [
                'description' => 'Service or Product',
                'quantity' => 1,
                'price' => 1000.00,
                'taxes_attributes' => [
                    [
                        'name' => 'IVA',
                        'category' => 'S',  // Standard rate
                        'percent' => 21.0,
                    ]
                ]
            ]
        ]
    ],
    'send_after_import' => false  // Set to true to send immediately
]);
```

#### Retrieve an Invoice

```php
$invoice = $client->invoices->retrieve($invoiceId);
echo "Invoice {$invoice['number']}: €{$invoice['total']}\n";
```

#### Update an Invoice

```php
$invoice = $client->invoices->update($invoiceId, [
    'invoice' => [
        'extra_info' => 'Payment terms: 30 days net'
    ]
]);
```

#### Delete an Invoice

```php
$result = $client->invoices->delete($invoiceId);
```

#### List Invoices

```php
$invoices = $client->invoices->all($accountId, [
    'limit' => 25,
    'offset' => 0,
    'date_from' => '2025-01-01',
    'date_to' => '2025-12-31',
]);

foreach ($invoices as $invoice) {
    echo "Invoice {$invoice['number']}: €{$invoice['total']}\n";
}
```

#### Download Invoice Documents

Download invoices as PDF or in various XML formats:

```php
// Download invoice as PDF
$pdfData = $client->invoices->downloadPdf($invoiceId);
file_put_contents('invoice.pdf', $pdfData);

// Download with custom parameters
$pdfData = $client->invoices->downloadPdf($invoiceId, [
    'disposition' => 'attachment',
    'filename' => 'invoice-2025-001.pdf'
]);

// Download Spanish Facturae 3.2.2 XML format
$facturaeData = $client->invoices->downloadAs($invoiceId, 'xml.facturae.3.2.2');
file_put_contents('invoice-facturae.xml', $facturaeData);

// Download UBL BIS3 format
$ublData = $client->invoices->downloadAs($invoiceId, 'xml.ubl.invoice.bis3');
file_put_contents('invoice-ubl.xml', $ublData);
```

**Note:** Available document types depend on your B2Brouter account configuration and the invoice type. Use the [B2Brouter API](https://developer.b2brouter.net/reference/get-document-types) to get a complete list of available document types.

#### Import Invoices

Import invoices from external sources or systems:

```php
$invoice = $client->invoices->import($accountId, [
    'invoice' => [
        'number' => 'EXT-2025-001',
        'date' => '2025-01-15',
        'currency' => 'EUR',
        'contact' => [
            'name' => 'External Customer',
            'tin_value' => 'ESB12345678',
            'country' => 'ES',
        ],
        'invoice_lines_attributes' => [
            [
                'description' => 'Imported Service',
                'quantity' => 1,
                'price' => 500.00,
                'taxes_attributes' => [
                    ['name' => 'IVA', 'category' => 'S', 'percent' => 21.0]
                ]
            ]
        ]
    ],
    'send_after_import' => true  // Optionally send immediately
]);
```

#### Additional Operations

```php
// Validate an invoice
$validation = $client->invoices->validate($invoiceId);

// Send an invoice to customer and generate tax reports
$result = $client->invoices->send($invoiceId);

// Mark invoice state (new, sent, paid, etc.)
$invoice = $client->invoices->markAs($invoiceId, [
    'state' => 'sent'
]);

// Acknowledge a received invoice
$result = $client->invoices->acknowledge($invoiceId, [
    'ack' => true
]);
```

### Tax Reports

Tax reports are automatically generated based on the fiscal obligations of the invoice issuer. For example, issuers subject to Spanish Verifactu requirements will have tax reports automatically created when they send invoices.

**Important**: Before tax reports can be generated, you must configure your `TaxReportSettings` for the account. This can be done either:
- Via the SDK using `$client->taxReportSettings` operations
- Through the B2BRouter web interface

Once configured, tax reports will contain critical compliance information including QR codes for verification.

#### Configure Tax Report Settings

Configure Verifactu or TicketBAI settings for your account:

```php
// Create Verifactu settings
$settings = $client->taxReportSettings->create($accountId, [
    'tax_report_setting' => [
        'code' => 'VeriFactu',
        'start_date' => '2025-01-01',
        'auto_generate' => true,
        'auto_send' => true,
        'reason_vat_exempt' => 'E1',
        'special_regime_key' => '01',
    ]
]);

// Retrieve settings
$settings = $client->taxReportSettings->retrieve($accountId, 'VeriFactu');

// Update settings
$settings = $client->taxReportSettings->update($accountId, 'VeriFactu', [
    'tax_report_setting' => [
        'auto_send' => false  // Disable automatic submission
    ]
]);

// List all settings
$allSettings = $client->taxReportSettings->all($accountId);

// Delete settings
$client->taxReportSettings->delete($accountId, 'VeriFactu');
```

#### Create Tax Reports Directly

For Point of Sale systems or when not using B2BRouter invoices:

```php
// Create a Verifactu tax report
$taxReport = $client->taxReports->create($accountId, [
    'tax_report' => [
        'type' => 'Verifactu',
        'invoice_date' => '2025-01-15',
        'invoice_number' => '2025-001',
        'description' => 'Professional services',
        'customer_party_tax_id' => 'B12345678',
        'customer_party_country' => 'es',
        'customer_party_name' => 'Cliente S.L.',
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
```

#### Retrieve Tax Reports

```php
// Get tax report ID from invoice response
$taxReportId = $invoice['tax_report_ids'][0];

// Retrieve the tax report with QR code
$taxReport = $client->taxReports->retrieve($taxReportId);

echo "Tax Report ID: {$taxReport['id']}\n";
echo "State: {$taxReport['state']}\n";

// Save QR code (base64 encoded PNG)
if (!empty($taxReport['qr'])) {
    file_put_contents('qr_code.png', base64_decode($taxReport['qr']));
}
```

#### List Tax Reports

```php
$taxReports = $client->taxReports->all($accountId, [
    'limit' => 25,
    'offset' => 0,
    'invoice_id' => $invoiceId,        // Filter by invoice
    'sent_at_from' => '2025-01-01',    // Filter by sent date
]);

foreach ($taxReports as $report) {
    echo "Tax Report: {$report['label']} - {$report['state']}\n";
}
```

#### Download Tax Report XML

```php
$xml = $client->taxReports->download($taxReportId);
file_put_contents("tax_report_{$taxReportId}.xml", $xml);
```

#### Update Tax Reports (Corrections)

Create corrections (subsanación) for Verifactu tax reports:

```php
$correctedReport = $client->taxReports->update($taxReportId, [
    'tax_report' => [
        'description' => 'CORRECTED: Updated description',
        'tax_inclusive_amount' => 133.1,
        'tax_amount' => 23.1,
        'tax_breakdowns' => [
            [
                'name' => 'IVA',
                'category' => 'S',
                'non_exemption_code' => 'S1',
                'percent' => 21.0,
                'taxable_base' => 110.0,
                'tax_amount' => 23.1,
                'special_regime_key' => '01'
            ]
        ]
    ]
]);
```

#### Delete Tax Reports (Annullations)

Create annullations (anulación) for tax reports:

```php
$annullation = $client->taxReports->delete($taxReportId);
echo "Annullation ID: {$annullation['id']}\n";
echo "State: {$annullation['state']}\n";
```

#### Tax Report States

Tax reports go through several states:

- **processing** - Initial state, chaining and submission in progress
- **registered** - Successfully submitted and accepted by tax authority
- **error** - Submission failed
- **registered_with_errors** - Submitted but with warnings
- **annulled** - Cancelled/voided

For more details, see [Tax Reports Documentation](docs/TAX_REPORTS.md).

### Spanish Invoicing with Verifactu

B2BRouter provides full compliance with the Spanish Anti-Fraud Law (Law 11/2021) and Verifactu requirements. When you create invoices for Spanish customers, B2BRouter automatically:

- Generates compliant tax reports
- Computes digital fingerprints and hash chains
- Submits reports to the Spanish Tax Authority (AEAT)
- Generates QR codes for invoice verification
- Handles rate limiting and retry logic

#### Complete Example

```php
<?php

require_once 'vendor/autoload.php';

use B2BRouter\B2BRouterClient;

$client = new B2BRouterClient($_ENV['B2B_API_KEY']);
$accountId = $_ENV['B2B_ACCOUNT_ID'];

// Create and send a Spanish invoice
$invoice = $client->invoices->create($accountId, [
    'invoice' => [
        'number' => 'INV-ES-2025-001',
        'date' => date('Y-m-d'),
        'due_date' => date('Y-m-d', strtotime('+30 days')),
        'currency' => 'EUR',
        'language' => 'es',
        'contact' => [
            'name' => 'Cliente Ejemplo SA',
            'tin_value' => 'ESB12345678',
            'country' => 'ES',
            'address' => 'Calle Gran Vía, 123',
            'city' => 'Madrid',
            'postalcode' => '28013',
            'email' => 'facturacion@ejemplo.com',
        ],
        'invoice_lines_attributes' => [
            [
                'description' => 'Servicios de consultoría',
                'quantity' => 10,
                'price' => 150.00,
                'taxes_attributes' => [
                    [
                        'name' => 'IVA',
                        'category' => 'S',  // Standard rate (21%)
                        'percent' => 21.0,
                    ]
                ]
            ]
        ],
    ],
    'send_after_import' => true  // Send immediately and generate tax report
]);

echo "Invoice created: {$invoice['id']}\n";
echo "State: {$invoice['state']}\n";

// Get the tax report
if (!empty($invoice['tax_report_ids'])) {
    $taxReportId = $invoice['tax_report_ids'][0];
    $taxReport = $client->taxReports->retrieve($taxReportId);

    echo "Tax Report ID: {$taxReport['id']}\n";
    echo "Tax Report State: {$taxReport['state']}\n";
    echo "QR Code: " . (!empty($taxReport['qr']) ? 'Generated' : 'Pending') . "\n";
    echo "Verification URL: {$taxReport['identifier']}\n";
}
```


For comprehensive information about Spanish invoicing and Verifactu compliance, see the [Spanish Invoicing Guide](docs/SPANISH_INVOICING.md).

## Pagination

The SDK provides automatic pagination support through the Collection class:

```php
$invoices = $client->invoices->all($accountId, [
    'limit' => 25,
    'offset' => 0,
]);

// Iterate through current page
foreach ($invoices as $invoice) {
    echo "Invoice: {$invoice['number']}\n";
}

// Check pagination info
echo "Total invoices: {$invoices->getTotal()}\n";
echo "Current count: {$invoices->count()}\n";
echo "Has more: " . ($invoices->hasMore() ? 'yes' : 'no') . "\n";
```

### Paginate Through All Results

```php
$offset = 0;
$limit = 100;
$allInvoices = [];

do {
    $page = $client->invoices->all($accountId, [
        'limit' => $limit,
        'offset' => $offset,
    ]);

    foreach ($page as $invoice) {
        $allInvoices[] = $invoice;
    }

    $offset += $limit;
} while ($page->hasMore());

echo "Fetched " . count($allInvoices) . " total invoices\n";
```

## Error Handling

The SDK provides specific exception types for different error scenarios:

```php
use B2BRouter\Exception\ApiErrorException;
use B2BRouter\Exception\AuthenticationException;
use B2BRouter\Exception\PermissionException;
use B2BRouter\Exception\ResourceNotFoundException;
use B2BRouter\Exception\InvalidRequestException;
use B2BRouter\Exception\ApiConnectionException;

try {
    $invoice = $client->invoices->create($accountId, [
        'invoice' => [ /* ... */ ]
    ]);
} catch (AuthenticationException $e) {
    // Invalid API key (401)
    echo "Authentication failed: {$e->getMessage()}\n";
    exit(1);
} catch (PermissionException $e) {
    // Insufficient permissions (403)
    echo "Permission denied: {$e->getMessage()}\n";
    exit(1);
} catch (ResourceNotFoundException $e) {
    // Resource not found (404)
    echo "Not found: {$e->getMessage()}\n";
    exit(1);
} catch (InvalidRequestException $e) {
    // Invalid parameters (400, 422)
    echo "Invalid request: {$e->getMessage()}\n";
    echo "HTTP Status: {$e->getHttpStatus()}\n";

    // Get detailed error information
    $errorBody = $e->getJsonBody();
    if ($errorBody) {
        echo "Error details: " . json_encode($errorBody, JSON_PRETTY_PRINT) . "\n";
    }

    exit(1);
} catch (ApiConnectionException $e) {
    // Network/connection errors
    echo "Connection error: {$e->getMessage()}\n";
    exit(1);
} catch (ApiErrorException $e) {
    // All other API errors (500, etc.)
    echo "API error: {$e->getMessage()}\n";
    echo "HTTP Status: {$e->getHttpStatus()}\n";
    echo "Request ID: {$e->getRequestId()}\n";
    exit(1);
}
```

### Request ID Logging

Always log the request ID when reporting errors to support:

```php
try {
    // API call
} catch (ApiErrorException $e) {
    error_log("API Error - Request ID: {$e->getRequestId()}, Message: {$e->getMessage()}");
}
```

## Examples

The `examples/` directory contains complete working examples demonstrating all SDK features:

### Invoice Operations
- **create_simple_invoice.php** - Create a simple invoice with one line item
- **create_detailed_invoice.php** - Create a multi-line invoice with calculations
- **download_invoice_documents.php** - Download invoices as PDF and XML formats (Facturae, UBL BIS3)
- **list_invoices.php** - List and filter invoices with pagination
- **paginate_all_invoices.php** - Iterate through all invoices efficiently
- **update_invoice.php** - Update an existing invoice
- **invoice_workflow.php** - Complete invoice lifecycle (create, retrieve, validate, update, send)
- **invoices.php** - Comprehensive CRUD operations demo

### Tax Reporting
- **tax_reports.php** - Complete CRUD operations for Verifactu and TicketBAI
- **verifactu_tax_report.php** - Verifactu-specific workflow with corrections and annullations
- **ticketbai_tax_report.php** - TicketBAI-specific workflow for Basque Country
- **list_tax_reports.php** - List and filter tax reports with analysis
- **tax_report_setup.php** - Configure tax report settings (Verifactu, TicketBAI)

### Spanish Compliance
- **invoicing_in_spain_with_verifactu.php** - Complete example of Spanish invoicing with automatic Verifactu compliance, tax report generation, and QR code retrieval

### Running Examples

1. **Setup environment variables:**
   ```bash
   cp .env.example .env
   # Edit .env and add your B2B_API_KEY and B2B_ACCOUNT_ID
   ```

2. **Install dependencies:**
   ```bash
   composer install
   ```

3. **Run any example:**
   ```bash
   # Invoice examples
   php examples/create_simple_invoice.php
   php examples/download_invoice_documents.php

   # Tax report examples
   php examples/tax_reports.php
   php examples/verifactu_tax_report.php

   # Spanish compliance
   php examples/invoicing_in_spain_with_verifactu.php
   ```

All examples automatically load credentials from your `.env` file via `examples/bootstrap.php`.

## Development

### Running Tests

The SDK includes a comprehensive test suite. To run tests:

```bash
# Install development dependencies
composer install

# Run unit tests (fast, excludes external integration tests)
composer test

# Run all tests including external integration tests
composer test:all

# Run only external integration tests
composer test:external

# Run tests with coverage
composer test:coverage
```

By default, `composer test` excludes external integration tests that make real HTTP requests to external services. This keeps the test suite fast and reliable for development.

For more information about contributing, setting up your development environment, and coding standards, see the [Developer Guide](docs/DEVELOPER_GUIDE.md).

## API Reference

### Complete Feature Matrix

The SDK provides comprehensive coverage of the B2BRouter API with the following operations:

#### Invoice Service (`$client->invoices`)

| Method | HTTP | Endpoint | Description |
|--------|------|----------|-------------|
| `create()` | POST | `/accounts/{account}/invoices` | Create a new invoice |
| `retrieve()` | GET | `/invoices/{id}` | Retrieve invoice details |
| `update()` | PUT | `/invoices/{id}` | Update an existing invoice |
| `delete()` | DELETE | `/invoices/{id}` | Delete an invoice |
| `all()` | GET | `/accounts/{account}/invoices` | List invoices (paginated) |
| `import()` | POST | `/accounts/{account}/invoices/import` | Import invoice from external source |
| `validate()` | GET | `/invoices/{id}/validate` | Validate invoice structure |
| `send()` | POST | `/invoices/send_invoice/{id}` | Send invoice and generate tax reports |
| `acknowledge()` | POST | `/invoices/{id}/ack` | Acknowledge received invoice |
| `markAs()` | POST | `/invoices/{id}/mark_as` | Update invoice state |
| `downloadAs()` | GET | `/invoices/{id}/as/{documentType}` | Download in specified format |
| `downloadPdf()` | GET | `/invoices/{id}/as/pdf.invoice` | Download as PDF (convenience) |

**Supported Download Formats:**
- `pdf.invoice` - PDF format
- `xml.facturae.3.2.2` - Spanish Facturae XML
- `xml.ubl.invoice.bis3` - Universal Business Language BIS3
- Additional formats per account configuration

**Common Query Parameters:**
- Pagination: `offset`, `limit` (max 500)
- Date filtering: `date_from`, `date_to`, `due_date_from`, `due_date_to`
- State filtering: `new`, `sent`, `paid`, `error`, `refused`, etc.
- Search: `number`, `taxcode`

#### Tax Report Service (`$client->taxReports`)

| Method | HTTP | Endpoint | Description |
|--------|------|----------|-------------|
| `create()` | POST | `/accounts/{account}/tax_reports` | Create tax report directly |
| `retrieve()` | GET | `/tax_reports/{id}` | Retrieve tax report with QR code |
| `all()` | GET | `/accounts/{account}/tax_reports` | List tax reports (paginated) |
| `download()` | GET | `/tax_reports/{id}/download` | Download XML representation |
| `update()` | PATCH | `/tax_reports/{id}` | Create correction (subsanación) |
| `delete()` | DELETE | `/tax_reports/{id}` | Create annullation (anulación) |

**Supported Tax Report Types:**
- `Verifactu` - Spanish compliance (AEAT)
- `TicketBai` - Basque Country compliance
- `SDI` - Italian Sistema di Interscambio
- `KSeF` - Polish National e-Invoicing System
- `Zatca` - Saudi Arabian e-invoicing

**Tax Report States:**
- `processing` - Being chained and prepared
- `registered` - Successfully submitted
- `error` - Submission failed
- `registered_with_errors` - Submitted with warnings
- `annulled` - Cancelled

**Query Parameters:**
- `invoice_id` - Filter by invoice
- `sent_at_from`, `sent_at_to` - Filter by submission date
- `updated_at_from`, `updated_at_to` - Filter by update date

#### Tax Report Setting Service (`$client->taxReportSettings`)

| Method | HTTP | Endpoint | Description |
|--------|------|----------|-------------|
| `create()` | POST | `/accounts/{account}/tax_report_settings` | Configure tax authority settings |
| `retrieve()` | GET | `/accounts/{account}/tax_report_settings/{code}` | Get specific settings |
| `update()` | PUT | `/accounts/{account}/tax_report_settings/{code}` | Update settings |
| `all()` | GET | `/accounts/{account}/tax_report_settings` | List all settings |
| `delete()` | DELETE | `/accounts/{account}/tax_report_settings/{code}` | Delete settings |

**Common Setting Codes:**
- `VeriFactu` - Spanish Verifactu configuration
- `TicketBai` - TicketBAI configuration

### SDK Architecture

#### Core Classes

- **B2BRouterClient** - Main client with configuration and service access
- **ApiResource** - Base class for all API operations with request handling
- **Collection** - Paginated result wrapper (implements Iterator, Countable)

#### Service Classes

- **InvoiceService** - All invoice operations (lib/B2BRouter/Service/InvoiceService.php:1)
- **TaxReportService** - Tax report operations (lib/B2BRouter/Service/TaxReportService.php:1)
- **TaxReportSettingService** - Settings management (lib/B2BRouter/Service/TaxReportSettingService.php:1)

#### Exception Hierarchy

All exceptions extend `ApiErrorException` and include:
- HTTP status code
- Request ID (for support)
- Response body (JSON)
- HTTP headers

```
ExceptionInterface
└── ApiErrorException (Base)
    ├── ApiConnectionException (Network errors)
    ├── AuthenticationException (401 Unauthorized)
    ├── PermissionException (403 Forbidden)
    ├── ResourceNotFoundException (404 Not Found)
    └── InvalidRequestException (400, 422 Validation errors)
```

### Configuration Options

```php
$client = new B2BRouterClient('api-key', [
    'api_base' => 'https://api.b2brouter.net',     // API endpoint
    'api_version' => '2025-10-13',                  // API version
    'timeout' => 80,                                // Request timeout (seconds)
    'max_retries' => 3,                             // Retry attempts on connection failure
    'http_client' => $customClient                  // Custom HTTP client (optional)
]);
```

**Default Values:**
- `api_base`: `https://api-staging.b2brouter.net` (staging)
- `api_version`: `2025-10-13`
- `timeout`: `80` seconds
- `max_retries`: `3` attempts

## Documentation

### SDK Documentation
- **[API Reference](docs/API_REFERENCE.md)** - Complete PHP SDK reference with all methods, parameters, and examples
- **[Spanish Invoicing Guide](docs/SPANISH_INVOICING.md)** - Comprehensive guide for Spanish Verifactu compliance
- **[Tax Reports Documentation](docs/TAX_REPORTS.md)** - Detailed tax reporting documentation for Verifactu and TicketBAI
- **[Developer Guide](docs/DEVELOPER_GUIDE.md)** - Contributing, development setup, and IDE configuration

### B2BRouter Platform Documentation
- **[B2BRouter API Reference](https://developer.b2brouter.net/v2025-10-13/reference)** - REST API documentation
- **[Verifactu Guide](https://developer.b2brouter.net/v2025-10-13/docs/verifactu)** - Complete B2BRouter Verifactu guide
- **[Developer Portal](https://developer.b2brouter.net)** - Guides, tutorials, and integration resources

## Support

- **Documentation**: https://developer.b2brouter.net
- **Email**: sdk@b2brouter.net
- **Issues**: Please report bugs and feature requests via GitHub Issues

When reporting issues, please include:
- PHP version
- SDK version
- Request ID (from error exceptions)
- Minimal code to reproduce the issue

## License

This library is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.
