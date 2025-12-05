# B2BRouter PHP SDK - API Reference

Complete technical reference for the B2BRouter PHP SDK.

## Table of Contents

- [Client Configuration](#client-configuration)
- [Invoice Service](#invoice-service)
- [Tax Report Service](#tax-report-service)
- [Tax Report Setting Service](#tax-report-setting-service)
- [Collections and Pagination](#collections-and-pagination)
- [Exception Handling](#exception-handling)
- [HTTP Client](#http-client)

---

## Client Configuration

### B2BRouterClient

The main entry point for interacting with the B2BRouter API.

**Location:** `lib/B2BRouter/B2BRouterClient.php`

#### Constructor

```php
public function __construct(string $apiKey, array $config = [])
```

**Parameters:**
- `$apiKey` (string, required) - Your B2BRouter API key
- `$config` (array, optional) - Configuration options

**Configuration Options:**

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `api_base` | string | `https://api-staging.b2brouter.net` | API base URL |
| `api_version` | string | `2025-10-13` | API version |
| `timeout` | int | `80` | Request timeout in seconds |
| `max_retries` | int | `3` | Maximum retry attempts for network failures |
| `http_client` | ClientInterface | `CurlClient` | Custom HTTP client implementation |

**Example:**

```php
use B2BRouter\B2BRouterClient;

// Basic initialization
$client = new B2BRouterClient('your-api-key');

// With custom configuration
$client = new B2BRouterClient('your-api-key', [
    'api_base' => 'https://api.b2brouter.net',
    'api_version' => '2025-10-13',
    'timeout' => 120,
    'max_retries' => 5
]);
```

#### Service Access

Services are accessed as magic properties via lazy loading:

```php
$client->invoices           // InvoiceService
$client->taxReports         // TaxReportService
$client->taxReportSettings  // TaxReportSettingService
```

---

## Invoice Service

Manages all invoice operations.

**Location:** `lib/B2BRouter/Service/InvoiceService.php`

**Access:** `$client->invoices`

### Methods

#### create()

Create a new invoice.

```php
public function create(string $accountId, array $params, array $options = []): array
```

**Parameters:**
- `$accountId` (string, required) - Account identifier
- `$params` (array, required) - Invoice data
- `$options` (array, optional) - Request options

**Returns:** Array containing the created invoice data

**Example:**

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
                'description' => 'Service',
                'quantity' => 1,
                'price' => 1000.00,
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
```

#### retrieve()

Retrieve a single invoice by ID.

```php
public function retrieve(string $invoiceId, array $options = []): array
```

**Parameters:**
- `$invoiceId` (string, required) - Invoice identifier
- `$options` (array, optional) - Request options

**Returns:** Array containing invoice data

**Example:**

```php
$invoice = $client->invoices->retrieve($invoiceId);
echo "Invoice {$invoice['number']}: {$invoice['total']} {$invoice['currency']}\n";
```

#### update()

Update an existing invoice.

```php
public function update(string $invoiceId, array $params, array $options = []): array
```

**Parameters:**
- `$invoiceId` (string, required) - Invoice identifier
- `$params` (array, required) - Fields to update
- `$options` (array, optional) - Request options

**Returns:** Array containing updated invoice data

**Example:**

```php
$invoice = $client->invoices->update($invoiceId, [
    'invoice' => [
        'extra_info' => 'Payment terms: 30 days net',
        'due_date' => '2025-03-15'
    ]
]);
```

#### delete()

Delete an invoice.

```php
public function delete(string $invoiceId, array $options = []): array
```

**Parameters:**
- `$invoiceId` (string, required) - Invoice identifier
- `$options` (array, optional) - Request options

**Returns:** Array containing deletion confirmation

**Example:**

```php
$result = $client->invoices->delete($invoiceId);
```

#### all()

List invoices with pagination and filters.

```php
public function all(string $accountId, array $params = [], array $options = []): Collection
```

**Parameters:**
- `$accountId` (string, required) - Account identifier
- `$params` (array, optional) - Query parameters
- `$options` (array, optional) - Request options

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `limit` | int | Number of results per page (max: 500) |
| `offset` | int | Starting position |
| `date_from` | string | Filter by invoice date (ISO 8601) |
| `date_to` | string | Filter by invoice date (ISO 8601) |
| `due_date_from` | string | Filter by due date |
| `due_date_to` | string | Filter by due date |
| `number` | string | Filter by invoice number |
| `taxcode` | string | Filter by customer tax code |
| `type` | string | Filter by invoice type |
| `new` | bool | Filter new invoices |
| `sent` | bool | Filter sent invoices |
| `paid` | bool | Filter paid invoices |
| `error` | bool | Filter invoices with errors |

**Returns:** Collection instance (iterable)

**Example:**

```php
$invoices = $client->invoices->all($accountId, [
    'limit' => 25,
    'offset' => 0,
    'date_from' => '2025-01-01',
    'date_to' => '2025-12-31',
    'sent' => true
]);

foreach ($invoices as $invoice) {
    echo "Invoice {$invoice['number']}: {$invoice['total']}\n";
}

echo "Total: {$invoices->getTotal()}\n";
echo "Has more: " . ($invoices->hasMore() ? 'yes' : 'no') . "\n";
```

#### import()

Import an invoice from an external source.

```php
public function import(string $accountId, array $params, array $options = []): array
```

**Parameters:**
- `$accountId` (string, required) - Account identifier
- `$params` (array, required) - Invoice data with `send_after_import` flag
- `$options` (array, optional) - Request options

**Returns:** Array containing imported invoice data

**Example:**

```php
$invoice = $client->invoices->import($accountId, [
    'invoice' => [
        'number' => 'EXT-001',
        'date' => '2025-01-15',
        'currency' => 'EUR',
        'contact' => [ /* contact data */ ],
        'invoice_lines_attributes' => [ /* line items */ ]
    ],
    'send_after_import' => true
]);
```

#### validate()

Validate an invoice's structure and data.

```php
public function validate(string $invoiceId, array $options = []): array
```

**Parameters:**
- `$invoiceId` (string, required) - Invoice identifier
- `$options` (array, optional) - Request options

**Returns:** Array containing validation results

**Example:**

```php
$validation = $client->invoices->validate($invoiceId);
if ($validation['valid']) {
    echo "Invoice is valid\n";
} else {
    print_r($validation['errors']);
}
```

#### send()

Send an invoice to the customer and generate tax reports.

```php
public function send(string $invoiceId, array $params = [], array $options = []): array
```

**Parameters:**
- `$invoiceId` (string, required) - Invoice identifier
- `$params` (array, optional) - Send options
- `$options` (array, optional) - Request options

**Returns:** Array containing send confirmation

**Example:**

```php
$result = $client->invoices->send($invoiceId);
echo "Invoice sent. Tax reports: " . implode(', ', $result['tax_report_ids']) . "\n";
```

#### acknowledge()

Acknowledge receipt of an invoice.

```php
public function acknowledge(string $invoiceId, array $params, array $options = []): array
```

**Parameters:**
- `$invoiceId` (string, required) - Invoice identifier
- `$params` (array, required) - Acknowledgment data
- `$options` (array, optional) - Request options

**Returns:** Array containing acknowledgment confirmation

**Example:**

```php
$result = $client->invoices->acknowledge($invoiceId, [
    'ack' => true,
    'ack_date' => date('Y-m-d')
]);
```

#### markAs()

Update an invoice's state.

```php
public function markAs(string $invoiceId, array $params, array $options = []): array
```

**Parameters:**
- `$invoiceId` (string, required) - Invoice identifier
- `$params` (array, required) - State update data
- `$options` (array, optional) - Request options

**Returns:** Array containing updated invoice data

**Example:**

```php
$invoice = $client->invoices->markAs($invoiceId, [
    'state' => 'paid',
    'paid_date' => date('Y-m-d')
]);
```

#### downloadAs()

Download an invoice in a specific format.

```php
public function downloadAs(string $invoiceId, string $documentType, array $params = []): string
```

**Parameters:**
- `$invoiceId` (string, required) - Invoice identifier
- `$documentType` (string, required) - Document format (e.g., `pdf.invoice`, `xml.facturae.3.2.2`)
- `$params` (array, optional) - Download options (`disposition`, `filename`)

**Returns:** Binary string (file content)

**Supported Document Types:**
- `pdf.invoice` - PDF format
- `xml.facturae.3.2.2` - Spanish Facturae XML 3.2.2
- `xml.ubl.invoice.bis3` - UBL BIS3 format
- Additional formats per account configuration

**Example:**

```php
// Download as PDF
$pdfData = $client->invoices->downloadAs($invoiceId, 'pdf.invoice', [
    'disposition' => 'attachment',
    'filename' => 'invoice-2025-001.pdf'
]);
file_put_contents('invoice.pdf', $pdfData);

// Download as Spanish Facturae XML
$xmlData = $client->invoices->downloadAs($invoiceId, 'xml.facturae.3.2.2');
file_put_contents('facturae.xml', $xmlData);
```

#### downloadPdf()

Convenience method to download an invoice as PDF.

```php
public function downloadPdf(string $invoiceId, array $params = []): string
```

**Parameters:**
- `$invoiceId` (string, required) - Invoice identifier
- `$params` (array, optional) - Download options

**Returns:** Binary string (PDF content)

**Example:**

```php
$pdfData = $client->invoices->downloadPdf($invoiceId);
file_put_contents('invoice.pdf', $pdfData);
```

---

## Tax Report Service

Manages tax report operations for compliance.

**Location:** `lib/B2BRouter/Service/TaxReportService.php`

**Access:** `$client->taxReports`

### Methods

#### create()

Create a tax report directly (for POS systems or direct reporting).

```php
public function create(string $accountId, array $params, array $options = []): array
```

**Parameters:**
- `$accountId` (string, required) - Account identifier
- `$params` (array, required) - Tax report data
- `$options` (array, optional) - Request options

**Returns:** Array containing created tax report data

**Example (Verifactu):**

```php
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

**Example (TicketBAI):**

```php
$taxReport = $client->taxReports->create($accountId, [
    'tax_report' => [
        'type' => 'TicketBai',
        'invoice_date' => '2025-01-15',
        'invoice_number' => '2025-001',
        'description' => 'Sale of products',
        'customer_party_tax_id' => 'B12345678',
        'customer_party_country' => 'es',
        'customer_party_name' => 'Cliente S.L.',
        'tax_inclusive_amount' => 121.0,
        'tax_amount' => 21.0,
        'invoice_type_code' => 'F1',
        'currency' => 'EUR',
        'tax_report_lines' => [
            [
                'quantity' => 1.0,
                'description' => 'Product A',
                'price' => 100.0,
                'tax_inclusive_amount' => 121.0,
                'tax_exclusive_amount' => 100.0,
                'tax_amount' => 21.0
            ]
        ],
        'tax_breakdowns' => [
            [
                'category' => 'S',
                'non_exempt' => true,
                'non_exemption_code' => 'S1',
                'percent' => 21.0,
                'taxable_base' => 100.0,
                'tax_amount' => 21.0
            ]
        ]
    ]
]);
```

#### retrieve()

Retrieve a single tax report by ID.

```php
public function retrieve(string $taxReportId, array $options = []): array
```

**Parameters:**
- `$taxReportId` (string, required) - Tax report identifier
- `$options` (array, optional) - Request options

**Returns:** Array containing tax report data including QR code (if available)

**Example:**

```php
$taxReport = $client->taxReports->retrieve($taxReportId);

echo "State: {$taxReport['state']}\n";
echo "Invoice: {$taxReport['invoice_number']}\n";
echo "Type: {$taxReport['type']}\n";

// Save QR code
if (!empty($taxReport['qr'])) {
    file_put_contents('qr.png', base64_decode($taxReport['qr']));
}

// Display verification URL
if (!empty($taxReport['identifier'])) {
    echo "Verify at: {$taxReport['identifier']}\n";
}
```

#### all()

List tax reports with pagination and filters.

```php
public function all(string $accountId, array $params = [], array $options = []): Collection
```

**Parameters:**
- `$accountId` (string, required) - Account identifier
- `$params` (array, optional) - Query parameters
- `$options` (array, optional) - Request options

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `limit` | int | Number of results per page |
| `offset` | int | Starting position |
| `invoice_id` | string | Filter by invoice ID |
| `sent_at_from` | string | Filter by sent date (ISO 8601) |
| `sent_at_to` | string | Filter by sent date (ISO 8601) |
| `updated_at_from` | string | Filter by update date (ISO 8601) |
| `updated_at_to` | string | Filter by update date (ISO 8601) |

**Returns:** Collection instance (iterable)

**Example:**

```php
$taxReports = $client->taxReports->all($accountId, [
    'limit' => 50,
    'invoice_id' => $invoiceId,
    'updated_at_from' => '2025-01-01'
]);

foreach ($taxReports as $report) {
    echo "Report {$report['id']}: {$report['label']} - {$report['state']}\n";
}
```

#### download()

Download the XML representation of a tax report.

```php
public function download(string $taxReportId, array $options = []): string
```

**Parameters:**
- `$taxReportId` (string, required) - Tax report identifier
- `$options` (array, optional) - Request options

**Returns:** Binary string (XML content)

**Example:**

```php
$xml = $client->taxReports->download($taxReportId);
file_put_contents("tax_report_{$taxReportId}.xml", $xml);
```

#### update()

Create a correction (subsanación) for a Verifactu tax report.

```php
public function update(string $taxReportId, array $params, array $options = []): array
```

**Parameters:**
- `$taxReportId` (string, required) - Tax report identifier to correct
- `$params` (array, required) - Corrected tax report data
- `$options` (array, optional) - Request options

**Returns:** Array containing the correction tax report

**Note:** Only supported for Verifactu. TicketBAI corrections (Zuzendu) are not currently supported.

**Example:**

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

echo "Correction created with ID: {$correctedReport['id']}\n";
```

#### delete()

Create an annullation (anulación) for a tax report.

```php
public function delete(string $taxReportId, array $options = []): array
```

**Parameters:**
- `$taxReportId` (string, required) - Tax report identifier to annul
- `$options` (array, optional) - Request options

**Returns:** Array containing the annullation tax report

**Note:** Supported for both Verifactu and TicketBAI.

**Example:**

```php
$annullation = $client->taxReports->delete($taxReportId);

echo "Annullation ID: {$annullation['id']}\n";
echo "State: {$annullation['state']}\n";

// Monitor annullation state
$finalStates = ['annulled', 'error'];
do {
    sleep(2);
    $status = $client->taxReports->retrieve($annullation['id']);
} while (!in_array($status['state'], $finalStates));
```

---

## Tax Report Setting Service

Manages tax authority configuration settings.

**Location:** `lib/B2BRouter/Service/TaxReportSettingService.php`

**Access:** `$client->taxReportSettings`

### Methods

#### create()

Create tax report settings for an account.

```php
public function create(string $accountId, array $params, array $options = []): array
```

**Parameters:**
- `$accountId` (string, required) - Account identifier
- `$params` (array, required) - Settings data
- `$options` (array, optional) - Request options

**Returns:** Array containing created settings

**Example:**

```php
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

#### retrieve()

Retrieve settings for a specific tax authority code.

```php
public function retrieve(string $accountId, string $code, array $options = []): array
```

**Parameters:**
- `$accountId` (string, required) - Account identifier
- `$code` (string, required) - Settings code (e.g., 'VeriFactu', 'TicketBai')
- `$options` (array, optional) - Request options

**Returns:** Array containing settings data

**Example:**

```php
$settings = $client->taxReportSettings->retrieve($accountId, 'VeriFactu');
echo "Auto-send: " . ($settings['auto_send'] ? 'enabled' : 'disabled') . "\n";
```

#### update()

Update existing tax report settings.

```php
public function update(string $accountId, string $code, array $params, array $options = []): array
```

**Parameters:**
- `$accountId` (string, required) - Account identifier
- `$code` (string, required) - Settings code
- `$params` (array, required) - Updated settings data
- `$options` (array, optional) - Request options

**Returns:** Array containing updated settings

**Example:**

```php
$settings = $client->taxReportSettings->update($accountId, 'VeriFactu', [
    'tax_report_setting' => [
        'auto_send' => false,
        'special_regime_key' => '07'
    ]
]);
```

#### all()

List all tax report settings for an account.

```php
public function all(string $accountId, array $params = [], array $options = []): Collection
```

**Parameters:**
- `$accountId` (string, required) - Account identifier
- `$params` (array, optional) - Query parameters
- `$options` (array, optional) - Request options

**Returns:** Collection instance (iterable)

**Example:**

```php
$allSettings = $client->taxReportSettings->all($accountId);

foreach ($allSettings as $setting) {
    echo "Settings: {$setting['code']} - " .
         ($setting['auto_send'] ? 'Auto-send ON' : 'Auto-send OFF') . "\n";
}
```

#### delete()

Delete tax report settings.

```php
public function delete(string $accountId, string $code, array $options = []): array
```

**Parameters:**
- `$accountId` (string, required) - Account identifier
- `$code` (string, required) - Settings code
- `$options` (array, optional) - Request options

**Returns:** Array containing deletion confirmation

**Example:**

```php
$result = $client->taxReportSettings->delete($accountId, 'VeriFactu');
```

---

## Collections and Pagination

### Collection Class

Wrapper for paginated API responses.

**Location:** `lib/B2BRouter/Collection.php`

**Implements:** `Iterator`, `Countable`

#### Methods

##### all()

Get all items in the current page as an array.

```php
public function all(): array
```

**Returns:** Array of items

**Example:**

```php
$invoices = $client->invoices->all($accountId, ['limit' => 25]);
$allItems = $invoices->all();
print_r($allItems);
```

##### getMeta()

Get pagination metadata.

```php
public function getMeta(): ?array
```

**Returns:** Array with pagination info or null

**Example:**

```php
$meta = $invoices->getMeta();
print_r($meta);
// ['total' => 150, 'offset' => 0, 'limit' => 25]
```

##### getTotal()

Get total number of items across all pages.

```php
public function getTotal(): ?int
```

**Returns:** Total count or null

**Example:**

```php
echo "Total invoices: {$invoices->getTotal()}\n";
```

##### getOffset()

Get current offset position.

```php
public function getOffset(): ?int
```

**Returns:** Current offset or null

##### getLimit()

Get page size limit.

```php
public function getLimit(): ?int
```

**Returns:** Limit or null

##### hasMore()

Check if there are more pages available.

```php
public function hasMore(): bool
```

**Returns:** True if more results exist

**Example:**

```php
if ($invoices->hasMore()) {
    $nextPage = $client->invoices->all($accountId, [
        'limit' => 25,
        'offset' => $invoices->getOffset() + $invoices->getLimit()
    ]);
}
```

##### count()

Get number of items in current page.

```php
public function count(): int
```

**Returns:** Item count for current page

**Example:**

```php
echo "Items in this page: {$invoices->count()}\n";
```

#### Iterator Implementation

Collections can be used directly in foreach loops:

```php
foreach ($invoices as $invoice) {
    echo "{$invoice['number']}\n";
}
```

---

## Exception Handling

### Exception Hierarchy

All SDK exceptions implement `ExceptionInterface` and extend `ApiErrorException`.

**Location:** `lib/B2BRouter/Exception/`

```
ExceptionInterface
└── ApiErrorException (Base exception)
    ├── ApiConnectionException (Network failures)
    ├── AuthenticationException (401 errors)
    ├── PermissionException (403 errors)
    ├── ResourceNotFoundException (404 errors)
    └── InvalidRequestException (400, 422 errors)
```

### ApiErrorException

Base exception class for all API errors.

**Location:** `lib/B2BRouter/Exception/ApiErrorException.php`

#### Methods

##### getHttpStatus()

Get HTTP status code.

```php
public function getHttpStatus(): ?int
```

**Returns:** HTTP status code or null

##### getHttpBody()

Get raw HTTP response body.

```php
public function getHttpBody(): ?string
```

**Returns:** Response body string or null

##### getJsonBody()

Get parsed JSON response body.

```php
public function getJsonBody(): ?array
```

**Returns:** Parsed array or null

##### getHttpHeaders()

Get HTTP response headers.

```php
public function getHttpHeaders(): ?array
```

**Returns:** Headers array or null

##### getRequestId()

Get request ID for support tracking.

```php
public function getRequestId(): ?string
```

**Returns:** Request ID from `X-Request-Id` header or null

#### Example Usage

```php
use B2BRouter\Exception\{
    AuthenticationException,
    InvalidRequestException,
    ResourceNotFoundException,
    ApiErrorException
};

try {
    $invoice = $client->invoices->create($accountId, $params);
} catch (AuthenticationException $e) {
    // Invalid API key (401)
    error_log("Authentication error: {$e->getMessage()}");
    error_log("Request ID: {$e->getRequestId()}");
} catch (InvalidRequestException $e) {
    // Validation errors (400, 422)
    echo "Validation failed: {$e->getMessage()}\n";
    echo "Status: {$e->getHttpStatus()}\n";

    $errors = $e->getJsonBody();
    if (isset($errors['errors'])) {
        print_r($errors['errors']);
    }
} catch (ResourceNotFoundException $e) {
    // Not found (404)
    echo "Resource not found: {$e->getMessage()}\n";
} catch (ApiErrorException $e) {
    // All other API errors
    error_log("API Error: {$e->getMessage()}");
    error_log("Request ID: {$e->getRequestId()}");
    error_log("Status: {$e->getHttpStatus()}");
}
```

### Specific Exceptions

#### ApiConnectionException

Thrown when network or connection errors occur.

**HTTP Status:** N/A (connection failed)

**Common Causes:**
- Network timeout
- DNS resolution failure
- Connection refused
- SSL/TLS errors

#### AuthenticationException

Thrown for authentication failures.

**HTTP Status:** 401 Unauthorized

**Common Causes:**
- Invalid API key
- Expired API key
- Missing API key

#### PermissionException

Thrown when access is denied.

**HTTP Status:** 403 Forbidden

**Common Causes:**
- Insufficient permissions
- Account disabled
- Feature not available for account

#### ResourceNotFoundException

Thrown when a resource is not found.

**HTTP Status:** 404 Not Found

**Common Causes:**
- Invalid invoice ID
- Invalid tax report ID
- Resource deleted

#### InvalidRequestException

Thrown for bad requests and validation errors.

**HTTP Status:** 400 Bad Request, 422 Unprocessable Entity

**Common Causes:**
- Missing required fields
- Invalid field values
- Validation failures
- Malformed request

---

## HTTP Client

### ClientInterface

Interface for HTTP client implementations.

**Location:** `lib/B2BRouter/HttpClient/ClientInterface.php`

#### Method

```php
public function request(
    string $method,
    string $url,
    array $headers,
    ?string $body,
    int $timeout
): array
```

**Parameters:**
- `$method` (string) - HTTP method (GET, POST, PUT, PATCH, DELETE)
- `$url` (string) - Full URL
- `$headers` (array) - HTTP headers
- `$body` (string|null) - Request body
- `$timeout` (int) - Timeout in seconds

**Returns:** Array with keys:
- `status` (int) - HTTP status code
- `headers` (array) - Response headers
- `body` (string) - Response body

### CurlClient

Default cURL-based HTTP client implementation.

**Location:** `lib/B2BRouter/HttpClient/CurlClient.php`

**Implements:** `ClientInterface`

#### Features

- Automatic retry logic with exponential backoff
- Connection timeout: 30 seconds (fixed)
- Request timeout: configurable (default: 80 seconds)
- Proper header parsing
- SSL/TLS support
- Support for JSON and binary responses

#### Retry Logic

- **Trigger:** Only on `ApiConnectionException` (network errors)
- **Max retries:** Configurable (default: 3)
- **Backoff:** Exponential (1s, 2s, 4s, ...)
- **No retry:** HTTP errors (4xx, 5xx) are not retried

#### Custom HTTP Client

You can provide a custom HTTP client implementation:

```php
use B2BRouter\HttpClient\ClientInterface;

class CustomHttpClient implements ClientInterface
{
    public function request(
        string $method,
        string $url,
        array $headers,
        ?string $body,
        int $timeout
    ): array {
        // Your custom implementation
        // Must return: ['status' => int, 'headers' => array, 'body' => string]
    }
}

// Use custom client
$client = new B2BRouterClient('api-key', [
    'http_client' => new CustomHttpClient()
]);
```

---

## Constants and Enumerations

### API Versions

**Current:** `2025-10-13`

The API version controls the response format and available features. Always specify the version you've developed against.

### Tax Report Types

| Type | Description | Jurisdiction |
|------|-------------|-------------|
| `Verifactu` | Spanish AEAT compliance | Spain |
| `TicketBai` | Basque Country compliance | Spain (Basque Country) |
| `SDI` | Sistema di Interscambio | Italy |
| `KSeF` | National e-Invoicing System | Poland |
| `Zatca` | E-invoicing compliance | Saudi Arabia |

### Tax Report States

| State | Description | Final? |
|-------|-------------|--------|
| `processing` | Being chained and prepared | No |
| `registered` | Successfully submitted | Yes |
| `error` | Submission failed | Yes |
| `registered_with_errors` | Submitted with warnings | Yes |
| `annulled` | Cancelled | Yes |

### Invoice States

Common invoice states:
- `new` - Created but not sent
- `sending` - Send in progress
- `sent` - Sent to customer
- `error` - Send failed
- `refused` - Rejected by customer
- `paid` - Payment received
- `closed` - Finalized

### Tax Categories (IVA)

| Code | Description | Typical Rate (Spain) |
|------|-------------|---------------------|
| `S` | Standard rate | 21% |
| `H` | High rate | 21% |
| `AA` | Low rate (reduced) | 10% |
| `AAA` | Super low rate | 4% |
| `E` | Exempt | 0% |
| `Z` | Zero rated | 0% |
| `AE` | Reverse charge | 0% |
| `NS` | Not subject | 0% |
| `G` | Free export | 0% |
| `O` | Out of scope | 0% |
| `K` | Intra-community | 0% |

---

## Best Practices

### Error Handling

Always catch specific exceptions and log request IDs:

```php
try {
    $result = $client->invoices->create($accountId, $params);
} catch (InvalidRequestException $e) {
    error_log("Validation error: {$e->getMessage()}");
    error_log("Request ID: {$e->getRequestId()}");
    error_log("Details: " . json_encode($e->getJsonBody()));
} catch (ApiErrorException $e) {
    error_log("API error: {$e->getMessage()}");
    error_log("Request ID: {$e->getRequestId()}");
}
```

### Pagination

Use Collection methods for efficient pagination:

```php
$offset = 0;
$limit = 100;

do {
    $page = $client->invoices->all($accountId, [
        'limit' => $limit,
        'offset' => $offset
    ]);

    foreach ($page as $invoice) {
        processInvoice($invoice);
    }

    $offset += $limit;
} while ($page->hasMore());
```

### Configuration

Use environment-specific configuration:

```php
$config = [
    'development' => [
        'api_base' => 'https://api-staging.b2brouter.net',
        'timeout' => 120,
    ],
    'production' => [
        'api_base' => 'https://api.b2brouter.net',
        'timeout' => 80,
    ],
];

$env = getenv('APP_ENV') ?: 'development';
$client = new B2BRouterClient($apiKey, $config[$env]);
```

### Custom HTTP Client

Implement custom logging or metrics:

```php
class LoggingHttpClient implements ClientInterface
{
    private $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function request(
        string $method,
        string $url,
        array $headers,
        ?string $body,
        int $timeout
    ): array {
        $start = microtime(true);
        error_log("API Request: {$method} {$url}");

        $response = $this->client->request($method, $url, $headers, $body, $timeout);

        $duration = microtime(true) - $start;
        error_log("API Response: {$response['status']} ({$duration}s)");

        return $response;
    }
}
```

---

## Version History

### v1.0.0 (2025-12-05)
- First stable release with semantic versioning guarantees
- Production-ready with backward compatibility commitments
- All features from 0.9.x series are now stable

### v0.9.1 (2025-11-19)
- Added invoice document download support (PDF and XML formats)
- Added `downloadAs()` and `downloadPdf()` methods to InvoiceService

### v0.9.0 (Initial Release)
- Complete invoice management (CRUD operations)
- Tax report operations (create, retrieve, list, download, update, delete)
- Tax report settings management
- Pagination support with Collection class
- Comprehensive exception hierarchy
- Automatic retry logic with exponential backoff
- Zero production dependencies

---

## Support

For additional help:

- **Documentation:** https://developer.b2brouter.net
- **API Reference:** https://developer.b2brouter.net/v2025-10-13/reference
- **Email:** sdk@b2brouter.net
- **Issues:** GitHub Issues

When reporting issues, always include:
- PHP version
- SDK version
- Request ID (from exception)
- Minimal code to reproduce
