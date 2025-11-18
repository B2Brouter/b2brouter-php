# B2BRouter PHP SDK Tests

This directory contains the test suite for the B2BRouter PHP SDK.

## Running Tests

### Prerequisites

Install development dependencies:

```bash
composer install
```

### Run All Tests

```bash
vendor/bin/phpunit
```

### Run Specific Test Suite

```bash
# Run only unit tests
vendor/bin/phpunit tests/Unit

# Run a specific test file
vendor/bin/phpunit tests/Unit/InvoiceServiceTest.php

# Run a specific test method
vendor/bin/phpunit --filter testCreateInvoice
```

### Run with Coverage

```bash
vendor/bin/phpunit --coverage-html coverage
```

Then open `coverage/index.html` in your browser.

### Run with Verbose Output

```bash
vendor/bin/phpunit --verbose
```

## Test Structure

```
tests/
├── bootstrap.php              # Test bootstrap file
├── TestCase.php              # Base test case with helpers
├── Mock/
│   └── MockHttpClient.php    # Mock HTTP client for testing
└── Unit/
    ├── B2BRouterClientTest.php    # Tests for main client
    ├── InvoiceServiceTest.php     # Tests for invoice service
    ├── CollectionTest.php         # Tests for pagination
    ├── ExceptionTest.php          # Tests for error handling
    └── HttpClientTest.php         # Tests for HTTP client
```

## Test Categories

### Unit Tests (`tests/Unit/`)

Unit tests verify individual components in isolation:

- **B2BRouterClientTest**: Client initialization, configuration, service access
- **InvoiceServiceTest**: All invoice CRUD operations and additional methods
- **CollectionTest**: Pagination, iteration, and metadata handling
- **ExceptionTest**: Error handling and exception types
- **HttpClientTest**: HTTP requests, retries, and connection handling

## Writing Tests

### Using MockHttpClient

The `MockHttpClient` allows you to test API calls without making actual HTTP requests:

```php
use B2BRouter\Tests\TestCase;

class MyTest extends TestCase
{
    public function testExample()
    {
        // Create client with mock HTTP client
        [$client, $mockHttp] = $this->createTestClient();

        // Queue a response
        $mockHttp->addResponse($this->mockResponse([
            'invoice' => ['id' => 'inv_123']
        ]));

        // Make API call
        $invoice = $client->invoices->retrieve('inv_123');

        // Verify request
        $request = $mockHttp->getLastRequest();
        $this->assertEquals('GET', $request['method']);
    }
}
```

### Helper Methods

The base `TestCase` class provides useful helpers:

- `createTestClient($options)` - Create a client with mock HTTP client
- `mockResponse($data, $status, $headers)` - Create a mock success response
- `mockErrorResponse($message, $status, $errorData)` - Create a mock error response

## Continuous Integration

Tests can be integrated into CI/CD pipelines:

```yaml
# Example GitHub Actions workflow
- name: Run tests
  run: |
    composer install
    vendor/bin/phpunit --coverage-clover coverage.xml
```

## Test Coverage

The test suite aims for high code coverage of critical paths:

- API client initialization and configuration
- All invoice CRUD operations
- Pagination and collection handling
- Error handling for all HTTP error codes
- HTTP client retry logic
- Request/response handling

## Notes

- Some HTTP client tests make actual requests to httpbin.org for integration testing
- These tests will be skipped if the test server is unavailable
- Mock clients are used for all other tests to avoid external dependencies
- All tests should be fast and reliable
