<?php

namespace B2BRouter\Tests\Unit;

use B2BRouter\Exception\AuthenticationException;
use B2BRouter\Exception\PermissionException;
use B2BRouter\Exception\ResourceNotFoundException;
use B2BRouter\Exception\InvalidRequestException;
use B2BRouter\Exception\ApiErrorException;
use B2BRouter\Tests\TestCase;

class ExceptionTest extends TestCase
{
    public function testAuthenticationException()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockErrorResponse(
            'Invalid API key provided',
            401
        ));

        try {
            $client->invoices->retrieve('inv_12345');
            $this->fail('Expected AuthenticationException to be thrown');
        } catch (AuthenticationException $e) {
            $this->assertEquals(401, $e->getHttpStatus());
            $this->assertStringContainsString('Invalid API key', $e->getMessage());
            $this->assertNotNull($e->getRequestId());
        }
    }

    public function testPermissionException()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockErrorResponse(
            'You do not have permission to access this resource',
            403
        ));

        $this->expectException(PermissionException::class);
        $this->expectExceptionMessage('You do not have permission');

        $client->invoices->retrieve('inv_12345');
    }

    public function testResourceNotFoundException()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockErrorResponse(
            'Invoice not found',
            404
        ));

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Invoice not found');

        $client->invoices->retrieve('inv_nonexistent');
    }

    public function testInvalidRequestException()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockErrorResponse(
            'Invalid request parameters',
            400,
            [
                'errors' => [
                    'number' => ['Invoice number is required']
                ]
            ]
        ));

        try {
            $client->invoices->create('test-account', [
                'invoice' => []
            ]);
            $this->fail('Expected InvalidRequestException to be thrown');
        } catch (InvalidRequestException $e) {
            $this->assertEquals(400, $e->getHttpStatus());
            $this->assertStringContainsString('Invalid request', $e->getMessage());
            $this->assertNotNull($e->getJsonBody());
            $this->assertArrayHasKey('errors', $e->getJsonBody());
        }
    }

    public function testInvalidRequestExceptionWith422()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockErrorResponse(
            'Unprocessable entity',
            422
        ));

        $this->expectException(InvalidRequestException::class);
        $client->invoices->update('inv_12345', [
            'invoice' => ['invalid_field' => 'value']
        ]);
    }

    public function testGenericApiErrorException()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockErrorResponse(
            'Internal server error',
            500
        ));

        $this->expectException(ApiErrorException::class);
        $this->expectExceptionMessage('Internal server error');

        $client->invoices->retrieve('inv_12345');
    }

    public function testExceptionWithJsonBody()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $errorData = [
            'error' => [
                'message' => 'Validation failed',
                'type' => 'validation_error',
                'code' => 'invalid_invoice'
            ]
        ];

        $mockHttp->addResponse($this->mockResponse($errorData, 400));

        try {
            $client->invoices->create('test-account', [
                'invoice' => ['number' => 'INV-001']
            ]);
        } catch (InvalidRequestException $e) {
            $this->assertEquals('Validation failed', $e->getMessage());
            $this->assertEquals($errorData, $e->getJsonBody());
        }
    }

    public function testExceptionWithHttpHeaders()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $headers = [
            'Content-Type' => 'application/json',
            'X-Request-Id' => 'req_test_12345'
        ];

        $mockHttp->addResponse($this->mockErrorResponse('Not found', 404, [], $headers));

        try {
            $client->invoices->retrieve('inv_12345');
        } catch (ResourceNotFoundException $e) {
            $this->assertEquals('req_test_12345', $e->getRequestId());
            $this->assertEquals($headers, $e->getHttpHeaders());
        }
    }

    public function testExceptionMessageExtraction()
    {
        [$client, $mockHttp] = $this->createTestClient();

        // Test with error.message format
        $mockHttp->addResponse($this->mockResponse([
            'error' => ['message' => 'Error from error.message']
        ], 400));

        try {
            $client->invoices->retrieve('inv_1');
            $this->fail('Expected exception');
        } catch (ApiErrorException $e) {
            $this->assertEquals('Error from error.message', $e->getMessage());
        }

        // Test with top-level message format
        $mockHttp->addResponse($this->mockResponse([
            'message' => 'Error from message'
        ], 400));

        try {
            $client->invoices->retrieve('inv_2');
            $this->fail('Expected exception');
        } catch (ApiErrorException $e) {
            $this->assertEquals('Error from message', $e->getMessage());
        }

        // Test with plain error string
        $mockHttp->addResponse($this->mockResponse([
            'error' => 'Plain error string'
        ], 400));

        try {
            $client->invoices->retrieve('inv_3');
            $this->fail('Expected exception');
        } catch (ApiErrorException $e) {
            $this->assertEquals('Plain error string', $e->getMessage());
        }
    }
}
