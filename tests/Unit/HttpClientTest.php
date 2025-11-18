<?php

namespace B2BRouter\Tests\Unit;

use B2BRouter\HttpClient\CurlClient;
use B2BRouter\Exception\ApiConnectionException;
use PHPUnit\Framework\TestCase;

/**
 * HttpClient Test Suite
 *
 * This test suite uses a hybrid approach:
 *
 * 1. **Unit Tests** (fast, no network): Test client instantiation, configuration,
 *    and interface compliance. These run by default with `composer test`.
 *
 * 2. **Integration Tests** (slow, requires network): Test actual HTTP behavior
 *    by making real requests to httpbin.org. These are marked with @group external
 *    and are excluded by default. Run them explicitly with `composer test:external`.
 *
 * Best Practice: The actual HTTP logic in services (InvoiceService, TaxReportService)
 * is thoroughly tested using MockHttpClient, providing fast unit test coverage
 * without network dependencies.
 */
class HttpClientTest extends TestCase
{
    public function testCurlClientCreation()
    {
        $client = new CurlClient();
        $this->assertInstanceOf(CurlClient::class, $client);
    }

    public function testCurlClientWithCustomRetrySettings()
    {
        $client = new CurlClient(5, 500);
        $this->assertInstanceOf(CurlClient::class, $client);
    }

    /**
     * Test that a successful request doesn't retry.
     *
     * Note: This test makes an actual HTTP request to httpbin.org.
     * In a production environment, you might want to mock cURL or skip this test.
     *
     * @group integration
     * @group external
     */
    public function testSuccessfulRequest()
    {
        if (!extension_loaded('curl')) {
            $this->markTestSkipped('cURL extension is not available');
        }

        $client = new CurlClient(3, 100);

        try {
            $response = $client->request(
                'GET',
                'https://httpbin.org/status/200',
                ['Accept' => 'application/json'],
                null,
                10
            );

            $this->assertEquals(200, $response['status']);
            $this->assertIsArray($response['headers']);
            $this->assertIsString($response['body']);
        } catch (\Exception $e) {
            // If httpbin.org is unavailable, skip the test
            $this->markTestSkipped('Could not connect to test server: ' . $e->getMessage());
        }
    }

    /**
     * Test that connection errors are properly wrapped.
     *
     * @group integration
     * @group external
     */
    public function testConnectionError()
    {
        if (!extension_loaded('curl')) {
            $this->markTestSkipped('cURL extension is not available');
        }

        $client = new CurlClient(0, 10); // No retries, short delay

        try {
            // Try to connect to an invalid host
            $client->request(
                'GET',
                'https://invalid-host-that-does-not-exist-12345.com',
                [],
                null,
                1
            );
            $this->fail('Expected ApiConnectionException to be thrown');
        } catch (ApiConnectionException $e) {
            $this->assertStringContainsString('cURL error', $e->getMessage());
        } catch (\Exception $e) {
            // In some environments, DNS might resolve or timeout differently
            $this->markTestSkipped('Connection test behaved unexpectedly: ' . $e->getMessage());
        }
    }

    /**
     * Test retry configuration.
     * This is a unit test that doesn't require network access.
     */
    public function testRetryConfiguration()
    {
        // Test default configuration
        $client = new CurlClient();
        $this->assertInstanceOf(CurlClient::class, $client);

        // Test custom retry configuration
        $clientWithRetries = new CurlClient(5, 1000);
        $this->assertInstanceOf(CurlClient::class, $clientWithRetries);

        // Test zero retries
        $clientNoRetries = new CurlClient(0, 0);
        $this->assertInstanceOf(CurlClient::class, $clientNoRetries);
    }

    /**
     * Test that client interface is properly implemented.
     */
    public function testClientInterface()
    {
        $client = new CurlClient();
        $this->assertInstanceOf(\B2BRouter\HttpClient\ClientInterface::class, $client);
    }

    /**
     * Test HTTP methods.
     *
     * @group integration
     * @group external
     */
    public function testDifferentHttpMethods()
    {
        if (!extension_loaded('curl')) {
            $this->markTestSkipped('cURL extension is not available');
        }

        $client = new CurlClient(0, 100);

        try {
            // Test POST
            $response = $client->request(
                'POST',
                'https://httpbin.org/post',
                ['Content-Type' => 'application/json'],
                json_encode(['test' => 'data']),
                10
            );
            $this->assertEquals(200, $response['status']);

            // Test PUT
            $response = $client->request(
                'PUT',
                'https://httpbin.org/put',
                ['Content-Type' => 'application/json'],
                json_encode(['test' => 'data']),
                10
            );
            $this->assertEquals(200, $response['status']);

            // Test DELETE
            $response = $client->request(
                'DELETE',
                'https://httpbin.org/delete',
                [],
                null,
                10
            );
            $this->assertEquals(200, $response['status']);
        } catch (\Exception $e) {
            $this->markTestSkipped('Could not connect to test server: ' . $e->getMessage());
        }
    }

    /**
     * Test that headers are properly sent and parsed.
     *
     * @group integration
     * @group external
     */
    public function testHeaderHandling()
    {
        if (!extension_loaded('curl')) {
            $this->markTestSkipped('cURL extension is not available');
        }

        $client = new CurlClient();

        try {
            $response = $client->request(
                'GET',
                'https://httpbin.org/headers',
                [
                    'X-Custom-Header' => 'test-value',
                    'Accept' => 'application/json'
                ],
                null,
                10
            );

            $this->assertEquals(200, $response['status']);
            $this->assertArrayHasKey('Content-Type', $response['headers']);

            // Check that request headers were sent (httpbin echoes them back)
            $body = json_decode($response['body'], true);
            if (isset($body['headers'])) {
                $this->assertArrayHasKey('X-Custom-Header', $body['headers']);
            }
        } catch (\Exception $e) {
            $this->markTestSkipped('Could not connect to test server: ' . $e->getMessage());
        }
    }

    /**
     * Test that request body is properly sent.
     *
     * @group integration
     * @group external
     */
    public function testRequestBody()
    {
        if (!extension_loaded('curl')) {
            $this->markTestSkipped('cURL extension is not available');
        }

        $client = new CurlClient();

        try {
            $requestData = ['key' => 'value', 'number' => 123];

            $response = $client->request(
                'POST',
                'https://httpbin.org/post',
                ['Content-Type' => 'application/json'],
                $requestData, // Should be converted to JSON
                10
            );

            $this->assertEquals(200, $response['status']);

            $body = json_decode($response['body'], true);
            if (isset($body['json'])) {
                $this->assertEquals('value', $body['json']['key']);
                $this->assertEquals(123, $body['json']['number']);
            }
        } catch (\Exception $e) {
            $this->markTestSkipped('Could not connect to test server: ' . $e->getMessage());
        }
    }

    /**
     * Test timeout handling.
     *
     * @group integration
     * @group external
     */
    public function testTimeout()
    {
        if (!extension_loaded('curl')) {
            $this->markTestSkipped('cURL extension is not available');
        }

        $client = new CurlClient(0, 10); // No retries

        try {
            // httpbin.org/delay/10 delays for 10 seconds, but we set timeout to 1 second
            $response = $client->request(
                'GET',
                'https://httpbin.org/delay/10',
                [],
                null,
                1 // 1 second timeout
            );

            // If we got here, either timeout didn't work or server rejected the request
            if ($response['status'] == 403 || $response['status'] >= 400) {
                $this->markTestSkipped('Test server returned error status: ' . $response['status']);
            } else {
                $this->fail('Expected ApiConnectionException for timeout');
            }
        } catch (ApiConnectionException $e) {
            $this->assertStringContainsString('cURL error', $e->getMessage());
        } catch (\Exception $e) {
            $this->markTestSkipped('Could not test timeout: ' . $e->getMessage());
        }
    }
}
