# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.2.0] - 2026-03-26

### Added

- **`AccountService`** (`$client->accounts`) — full account management:
  - `all()` — list accounts with Ransack query filtering
  - `create()` — create a new account (eDocSync subscriptions)
  - `retrieve()` — get account details
  - `update()` — update account fields
  - `delete()` — archive or delete an account
  - `unarchive()` — restore an archived account
  - `uploadLogo()` — upload account logo (binary)
  - `deleteLogo()` — remove account logo
- **`ContactService`** (`$client->contacts`) — full contact management:
  - `all()` — list contacts with filtering by name, type, and integration code
  - `create()` — create a new contact
  - `retrieve()` — get contact details
  - `update()` — update contact fields
  - `delete()` — delete a contact
- New examples: `accounts.php`, `contacts.php`
- `docs/VERSIONING.md` — SDK versioning policy documentation

## [1.1.0] - 2026-03-24

### Changed

- Default API version updated from `2025-10-13` to `2026-03-02`
- Request ID header updated to `X-B2B-API-Request-Id` (falls back to `X-Request-Id` for older API versions)

### API version `2026-03-02` migration guide

The following changes apply **only if you use API version `2026-03-02`** (the new default). If you pin `'api_version' => '2025-10-13'`, your existing code continues to work unchanged.

To keep the previous API version:

```php
$client = new B2BRouterClient('your-api-key', [
    'api_version' => '2025-10-13',
]);
```

#### Removed invoice fields

The following invoice-level fields have been removed. Use `allowance_charges_attributes` instead:

- `discount_amount`, `discount_percent`, `discount_text` — use `allowance_charges_attributes` with `allowance_charge_indicator: "allowance"`
- `charge_amount`, `charge_percent`, `charge_reason` — use `allowance_charges_attributes` with `allowance_charge_indicator: "charge"`
- `apply_taxes_to_charge` — use `allowance_charges_attributes` with individual `apply_taxes` settings
- `charge_is_reimbursable_expense` — use `allowance_charges_attributes` with individual `is_reimbursable_expense` settings

#### Removed invoice line fields

The following line-level fields have been removed. Use `allowance_charges_attributes` instead:

- `discount_amount`, `discount_percent`, `discount_text` — use `allowance_charges_attributes` with `allowance_charge_indicator: "allowance"`
- `charge_amount`, `charge_percent`, `charge_reason` — use `allowance_charges_attributes` with `allowance_charge_indicator: "charge"`

#### Invoice search: `taxcode` replaced by `query`

Before (API `2025-10-13`):
```php
$invoices = $client->invoices->all($accountId, ['taxcode' => 'ESB12345678']);
```

After (API `2026-03-02`):
```php
$invoices = $client->invoices->all($accountId, ['query' => 'tin_value=ESB12345678']);
```

#### Other API changes

- **`type_document` renamed to `type_code`** on invoices for document type codes (Peppol, CII, KSeF, FatturaPA)
- **Scheme fields** (`tin_scheme`, `cin_scheme`, `pin_scheme`) now return zero-padded 4-character strings (e.g., `"0007"` instead of `7`). Unknown scheme returns `nil` instead of `"0001"`.
- **POST create endpoints** now return `201 Created` instead of `200 OK` (SDK handles this transparently)
- **`contact_id` ignored** for `IssuedSimplifiedInvoice` — simplified invoices always use inline contact fields
- **Contact `is_provider`** now defaults to `true` when creating contacts via API

#### New API fields (no SDK changes required)

These fields are automatically available through the array-based response:

- Invoice: `base_quantity` (decimal), `tax_currency_code` (ISO 4217 string), `tax_amount_in_tax_currency` (decimal), `payments_on_account`
- TaxReport: `annulled_by_id`, `corrected_by_id` (integer, nullable), `payment_account_name`, `purchase_order_reference`, `sales_order_reference`, `tax_inclusive_amount_before_allowances_and_charges` (KSeF-specific)
- TaxReportLine: `item_seller_identifier`, `item_standard_identifier` (TicketBAI/KSeF)
- BankAccount: `is_default` (boolean)

## [1.0.0] - 2025-12-05

### First Stable Release

This is the first stable release of the B2BRouter PHP SDK. The SDK is production-ready with semantic versioning guarantees. All future 1.x releases will maintain backward compatibility.

### Features

#### Invoice Management
- Complete CRUD operations (create, retrieve, update, delete, list)
- Domain-specific operations:
  - `validate()` - Validate invoice structure before sending
  - `send()` - Send invoice to customer and generate tax reports
  - `markAs()` - Update invoice state (new, sent, paid, etc.)
  - `acknowledge()` - Mark received invoices as acknowledged
  - `import()` - Import invoices from external sources
- Multi-format document downloads:
  - `downloadAs()` - Download in any supported format
  - `downloadPdf()` - Convenience method for PDF downloads
  - Support for PDF (`pdf.invoice`)
  - Support for Spanish Facturae XML (`xml.facturae.3.2.2`)
  - Support for UBL BIS3 (`xml.ubl.invoice.bis3`)
  - Additional formats based on account configuration

#### Tax Reports
- Full CRUD operations (create, retrieve, update, delete, list)
- Multi-jurisdiction support:
  - Spanish Verifactu (Law 11/2021 Anti-Fraud compliance)
  - TicketBAI (Basque Country: Álava, Bizkaia, Gipuzkoa)
  - Italian SDI (Sistema di Interscambio)
  - Polish KSeF (National e-Invoicing System)
  - Saudi Zatca (e-invoicing)
- Automatic tax report generation on invoice send
- QR code generation for invoice verification
- Digital fingerprint and hash chain computation
- Corrections (subsanación) via `update()`
- Annullations (anulación) via `delete()`
- XML download support

#### Tax Report Settings
- Configure tax authority settings (Verifactu, TicketBAI, etc.)
- Full CRUD operations
- Auto-generation and auto-send configuration
- Special regime and exemption settings

#### HTTP Client & Error Handling
- Automatic retry logic with exponential backoff (configurable, default: 3 retries)
- Configurable timeouts (request: 80s, connection: 30s)
- Custom HTTP client support via `ClientInterface`
- Comprehensive exception hierarchy:
  - `ApiErrorException` - Base exception for all API errors
  - `AuthenticationException` - Invalid API key (401)
  - `PermissionException` - Insufficient permissions (403)
  - `ResourceNotFoundException` - Resource not found (404)
  - `InvalidRequestException` - Validation errors (400, 422)
  - `ApiConnectionException` - Network/connection errors
- Rich exception context with HTTP status, headers, request ID

#### Collections & Pagination
- Collection class implementing Iterator and Countable interfaces
- Easy iteration with native `foreach` loops
- Pagination metadata (total, offset, limit)
- `hasMore()` helper for checking additional pages

#### Developer Experience
- Modern PHP 7.4+ with type hints throughout
- PSR-4 autoloading
- PSR-12 coding standards compliant
- Zero dependencies (only PHP extensions: cURL, JSON, mbstring)
- 15 working examples covering all features
- Comprehensive documentation:
  - README.md with quick start and examples
  - API_REFERENCE.md with complete method documentation
  - SPANISH_INVOICING.md with Verifactu compliance guide
  - TAX_REPORTS.md with tax reporting details
  - DEVELOPER_GUIDE.md with setup and best practices
- Environment configuration support via `.env` files
- Composer scripts for testing (`test`, `test:all`, `test:coverage`)

### Upgrade from 0.9.x

No breaking changes. Update your composer.json:

```json
{
  "require": {
    "b2brouter/b2brouter-php": "^1.0"
  }
}
```

Then run: `composer update b2brouter/b2brouter-php`

All 0.9.x code is fully compatible with 1.0.0.

### Requirements

- PHP 7.4 or higher
- cURL extension
- JSON extension
- mbstring extension

### Support

- **Documentation:** https://developer.b2brouter.net
- **Email:** sdk@b2brouter.net
- **Issues:** https://github.com/B2Brouter/b2brouter-php/issues

## [0.9.1] - 2025-11-19

### Added

- **Invoice document download support** - Download invoices in various formats
  - `InvoiceService::downloadAs($id, $documentType, $params)` - Download invoice in any supported format
  - `InvoiceService::downloadPdf($id, $params)` - Convenience method for PDF downloads
  - Support for PDF format (`pdf.invoice`)
  - Support for XML formats (Facturae `xml.facturae.3.2.2`, UBL BIS3 `xml.ubl.invoice.bis3`, and more)
  - Optional query parameters for disposition and custom filename
- **Binary response handling** - New `ApiResource::requestBinary()` method for non-JSON responses
  - Automatic Accept header determination based on document type
  - Proper error handling for binary endpoints (still parses JSON errors)
  - Returns raw binary data for PDF/XML downloads

### Documentation

- Added invoice document download examples to README
- Updated PHPDoc with comprehensive documentation for new methods
- Added unit tests for PDF, Facturae, and UBL downloads
- Documented available document type codes
- Added `examples/download_invoice_documents.php` - Complete example showing invoice creation and downloading in PDF and UBL BIS3 formats

### Technical Details

- Binary downloads return raw string data (PDF bytes, XML text, etc.)
- Accept headers automatically set: `application/pdf` for PDF formats, `application/xml` for XML formats
- All existing exception types work with download methods (404, 401, 403, etc.)
- Automatic retry logic applies to document downloads

## [0.9.0] - 2025-11-18

### Added

#### Core SDK Features
- Invoice CRUD operations (create, retrieve, update, delete, list)
- Invoice domain-specific operations:
  - `validate()` - Validate invoice structure before sending
  - `send()` - Send invoice to customer and/or tax authority
  - `markAs()` - Update invoice state (sent, paid, etc.)
  - `acknowledge()` - Mark received invoices as acknowledged
  - `import()` - Bulk import invoices
- Tax report management:
  - `retrieve()` - Get tax report details
  - `all()` - List tax reports with filtering
  - `download()` - Download XML tax report
  - `create()`, `update()`, `delete()` - CRUD operations
- Tax report settings configuration (CRUD operations)
- Collection-based pagination with Iterator and Countable interfaces

#### Tax Compliance
- Spanish Verifactu compliance support (Law 11/2021 Anti-Fraud)
- TicketBAI tax reporting (Basque Country: Álava, Bizkaia, Gipuzkoa)
- Support for multiple tax jurisdictions:
  - Spain (Verifactu)
  - Basque Country (TicketBAI)
  - Italy (SDI)
  - Poland (KSeF)
  - Saudi Arabia (Zatca)
- Automatic tax report generation on invoice send
- QR code generation for invoice verification
- Digital fingerprint and hash chain computation

#### Error Handling
- Comprehensive exception hierarchy:
  - `ApiErrorException` - Base exception for all API errors
  - `AuthenticationException` - Invalid API key (401)
  - `PermissionException` - Insufficient permissions (403)
  - `ResourceNotFoundException` - Resource not found (404)
  - `InvalidRequestException` - Validation errors (400, 422)
  - `ApiConnectionException` - Network/connection errors
- Rich exception context:
  - HTTP status code
  - Raw HTTP body
  - Parsed JSON response
  - HTTP headers
  - Request ID for support tracking

#### HTTP Client
- Automatic retry logic with exponential backoff (default: 3 retries)
- Configurable timeouts (request: 80s, connection: 30s)
- Custom HTTP client support via `ClientInterface`
- cURL-based default implementation
- Mock HTTP client for testing

#### Testing & Quality
- Comprehensive test suite with PHPUnit 9.x
- Unit tests for all major components:
  - B2BRouterClientTest
  - InvoiceServiceTest
  - TaxReportServiceTest
  - TaxReportSettingServiceTest
  - CollectionTest
  - ExceptionTest
  - HttpClientTest
- Mock HTTP client for isolated testing
- Test grouping (unit, integration, external)
- GitHub Actions CI/CD pipeline:
  - Automated testing on push/PR
  - Composer validation
  - Dependency caching
- Static analysis with PHPStan
- Code standards checking with PHP_CodeSniffer

#### Documentation
- Comprehensive README.md with:
  - Quick start guide
  - Configuration options
  - Core concepts (Invoices, Tax Reports, Spanish Invoicing)
  - Pagination examples
  - Error handling examples
- Specialized guides:
  - `DEVELOPER_GUIDE.md` - Setup, IDE configuration, testing, best practices
  - `SPANISH_INVOICING.md` - Verifactu compliance guide
  - `TAX_REPORTS.md` - Tax reporting system documentation
- 14 working examples:
  - `create_simple_invoice.php`
  - `create_detailed_invoice.php`
  - `list_invoices.php`
  - `paginate_all_invoices.php`
  - `update_invoice.php`
  - `invoice_workflow.php`
  - `invoices.php`
  - `invoicing_in_spain_with_verifactu.php`
  - `tax_reports.php`
  - `verifactu_tax_report.php`
  - `ticketbai_tax_report.php`
  - And more...
- Environment configuration support (`.env.example`)

#### Developer Experience
- Modern PHP 7.4+ with type hints throughout
- PSR-4 autoloading
- Composer scripts for testing:
  - `composer test` - Unit tests (fast)
  - `composer test:all` - All tests including integration
  - `composer test:external` - External integration tests
  - `composer test:coverage` - HTML coverage report
- Clean service-based API design
- Lazy service loading (services created on first access)
- Iterator support for collections (native `foreach` loops)

### Requirements

- PHP 7.4 or higher
- cURL extension
- JSON extension
- mbstring extension

### Notes

This is a **beta release** (v0.9.x) intended for early adopters and development/testing purposes.

**Stability:**
- ✅ The SDK is feature-complete and well-tested
- ✅ Suitable for WooCommerce plugin development and testing
- ⚠️ API may undergo minor refinements before v1.0.0
- ⚠️ Production use should pin to specific version: `"b2brouter/b2brouter-php": "0.9.0"`

**What's Coming in v1.0.0:**
- Potential minor API refinements based on early adopter feedback

**Upgrade Path:**
- Beta users: `^0.9.0` (allows 0.9.1, 0.9.2, etc.)
- After v1.0.0: `^1.0` (semantic versioning guarantees)

### Support

- **Documentation:** https://developer.b2brouter.net
- **Email:** sdk@b2brouter.net
- **Issues:** https://github.com/B2Brouter/b2brouter-php/issues

---

[Unreleased]: https://github.com/B2Brouter/b2brouter-php/compare/v1.2.0...HEAD
[1.2.0]: https://github.com/B2Brouter/b2brouter-php/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/B2Brouter/b2brouter-php/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/B2Brouter/b2brouter-php/compare/v0.9.1...v1.0.0
[0.9.1]: https://github.com/B2Brouter/b2brouter-php/compare/v0.9.0...v0.9.1
[0.9.0]: https://github.com/B2Brouter/b2brouter-php/releases/tag/v0.9.0
