<?php

namespace B2BRouter\Tests;

use B2BRouter\B2BRouterClient;
use B2BRouter\Tests\Mock\MockHttpClient;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Base test case with helper methods.
 */
abstract class TestCase extends PHPUnitTestCase
{
    /**
     * Create a test client with mock HTTP client.
     *
     * @param array $options
     * @return array [B2BRouterClient, MockHttpClient]
     */
    protected function createTestClient(array $options = [])
    {
        $mockClient = new MockHttpClient();

        $options = array_merge([
            'api_key' => 'test_api_key_12345',
            'http_client' => $mockClient
        ], $options);

        $apiKey = $options['api_key'];
        unset($options['api_key']);

        $client = new B2BRouterClient($apiKey, $options);

        return [$client, $mockClient];
    }

    /**
     * Create a mock success response.
     *
     * @param array $data
     * @param int $status
     * @param array $headers
     * @return array
     */
    protected function mockResponse($data = [], $status = 200, $headers = [])
    {
        $defaultHeaders = [
            'Content-Type' => 'application/json',
            'X-Request-Id' => 'req_' . uniqid()
        ];

        // Merge with provided headers taking precedence
        $headers = array_merge($defaultHeaders, $headers);

        return [
            'body' => json_encode($data),
            'status' => $status,
            'headers' => $headers
        ];
    }

    /**
     * Create a mock error response.
     *
     * @param string $message
     * @param int $status
     * @param array $errorData
     * @param array $headers
     * @return array
     */
    protected function mockErrorResponse($message, $status = 400, $errorData = [], $headers = [])
    {
        $body = array_merge([
            'error' => [
                'message' => $message,
                'type' => 'api_error'
            ]
        ], $errorData);

        return $this->mockResponse($body, $status, $headers);
    }
}
