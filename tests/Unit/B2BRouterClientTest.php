<?php

namespace B2BRouter\Tests\Unit;

use B2BRouter\B2BRouterClient;
use B2BRouter\Service\InvoiceService;
use B2BRouter\Tests\TestCase;

class B2BRouterClientTest extends TestCase
{
    public function testClientInitialization()
    {
        $client = new B2BRouterClient('test_api_key');

        $this->assertEquals('test_api_key', $client->getApiKey());
        $this->assertEquals('https://api-staging.b2brouter.net', $client->getApiBase());
        $this->assertEquals('2025-10-13', $client->getApiVersion());
        $this->assertEquals(80, $client->getTimeout());
    }

    public function testClientWithCustomOptions()
    {
        $client = new B2BRouterClient('test_api_key', [
            'api_base' => 'https://api.b2brouter.net',
            'api_version' => '2024-01-01',
            'timeout' => 120
        ]);

        $this->assertEquals('https://api.b2brouter.net', $client->getApiBase());
        $this->assertEquals('2024-01-01', $client->getApiVersion());
        $this->assertEquals(120, $client->getTimeout());
    }

    public function testClientWithTrailingSlashInApiBase()
    {
        $client = new B2BRouterClient('test_api_key', [
            'api_base' => 'https://api.b2brouter.net/'
        ]);

        // Should remove trailing slash
        $this->assertEquals('https://api.b2brouter.net', $client->getApiBase());
    }

    public function testClientRequiresApiKey()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('API key cannot be empty');

        new B2BRouterClient('');
    }

    public function testGetInvoiceService()
    {
        $client = new B2BRouterClient('test_api_key');
        $service = $client->invoices;

        $this->assertInstanceOf(InvoiceService::class, $service);
    }

    public function testGetServiceReturnsSameInstance()
    {
        $client = new B2BRouterClient('test_api_key');
        $service1 = $client->invoices;
        $service2 = $client->invoices;

        $this->assertSame($service1, $service2);
    }

    public function testGetUnknownServiceThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown service: unknownservice');

        $client = new B2BRouterClient('test_api_key');
        $client->unknownservice;
    }
}
