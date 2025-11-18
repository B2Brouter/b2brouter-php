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

- **Simple and intuitive API** - Clean, modern PHP interface for the B2BRouter API
- **Automatic authentication** - Secure API key management built-in
- **Pagination support** - Easy iteration through large result sets
- **Comprehensive error handling** - Detailed exception hierarchy with request tracking
- **Automatic retries** - Network failures handled gracefully with exponential backoff
- **Type safety** - Full PHP 7.4+ support with type hints
- **Spanish compliance** - Built-in support for Verifactu and Spanish Anti-Fraud Law requirements
- **Tax reporting** - Automated tax report generation and submission to Spanish Tax Authority (AEAT)

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
php examples/invoices.php
php examples/create_simple_invoice.php
php examples/list_invoices.php

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

#### Additional Operations

```php
// Validate an invoice
$validation = $client->invoices->validate($invoiceId);

// Send an invoice to customer
$result = $client->invoices->send($invoiceId);

// Mark invoice state
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

#### Retrieve a Tax Report

```php
// Get tax report ID from invoice response
$taxReportId = $invoice['tax_report_ids'][0];

// Retrieve the tax report
$taxReport = $client->taxReports->retrieve($taxReportId);

echo "Tax Report ID: {$taxReport['id']}\n";
echo "State: {$taxReport['state']}\n";
```

#### List Tax Reports

```php
$taxReports = $client->taxReports->all($accountId, [
    'limit' => 25,
    'offset' => 0,
    'invoice_id' => $invoiceId,  // Filter by invoice
]);

foreach ($taxReports as $report) {
    echo "Tax Report: {$report['label']} - {$report['state']}\n";
}
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

The `examples/` directory contains complete working examples:

### Basic Operations
- **create_simple_invoice.php** - Create a simple invoice with one line item
- **create_detailed_invoice.php** - Create a multi-line invoice with calculations
- **list_invoices.php** - List and filter invoices
- **paginate_all_invoices.php** - Paginate through all invoices
- **update_invoice.php** - Update an existing invoice

### Workflows
- **invoice_workflow.php** - Complete invoice lifecycle (create, retrieve, validate, update, send)
- **invoices.php** - Comprehensive CRUD operations demo

### Spanish Compliance
- **invoicing_in_spain_with_verifactu.php** - Complete example of Spanish invoicing with automatic Verifactu compliance, tax report generation, and QR code retrieval

To run an example:

```bash
# Set your environment variables
export B2B_API_KEY=your-api-key
export B2B_ACCOUNT_ID=your-account-id

# Run the example
php examples/invoicing_in_spain_with_verifactu.php
```

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

## Documentation

- **[Spanish Invoicing Guide](docs/SPANISH_INVOICING.md)** - Comprehensive guide for Spanish Verifactu compliance
- **[Tax Reports Documentation](docs/TAX_REPORTS.md)** - Detailed tax reporting documentation
- **[Developer Guide](docs/DEVELOPER_GUIDE.md)** - Contributing and development setup
- **[API Reference](https://developer.b2brouter.net/v2025-10-13/reference)** - Complete API documentation
- [B2Brouter Verifactu Guide](https://developer.b2brouter.net/v2025-10-13/docs/verifactu) - Full B2Brouter Verifactu Guide.
- **[Developer Portal](https://developer.b2brouter.net)** - Guides and tutorials

## Support

- **Documentation**: https://developer.b2brouter.net
- **Email**: servicedelivery@b2brouter.net
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
