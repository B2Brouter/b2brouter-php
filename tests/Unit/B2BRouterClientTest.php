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
        $this->assertEquals('2026-04-20', $client->getApiVersion());
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

    public function testDefaultApiVersionHeaderSentInRequests()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'invoice' => ['id' => 'inv_1']
        ]));

        $client->invoices->retrieve('inv_1');

        $request = $mockHttp->getLastRequest();
        $this->assertEquals('2026-04-20', $request['headers']['X-B2B-API-Version']);
    }

    public function testCustomApiVersionHeaderSentInRequests()
    {
        [$client, $mockHttp] = $this->createTestClient([
            'api_version' => '2025-10-13'
        ]);

        $mockHttp->addResponse($this->mockResponse([
            'invoice' => ['id' => 'inv_1']
        ]));

        $client->invoices->retrieve('inv_1');

        $request = $mockHttp->getLastRequest();
        $this->assertEquals('2025-10-13', $request['headers']['X-B2B-API-Version']);
    }

    public function testGetUnknownServiceThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown service: unknownservice');

        $client = new B2BRouterClient('test_api_key');
        $client->unknownservice;
    }

    public function testVersionConstantExists()
    {
        $this->assertIsString(B2BRouterClient::VERSION);
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+/', B2BRouterClient::VERSION);
    }

    public function testDefaultUserAgent()
    {
        $client = new B2BRouterClient('test_api_key');
        $ua = $client->getUserAgent();

        $this->assertStringStartsWith('B2BRouter-PHP/' . B2BRouterClient::VERSION . ' ', $ua);
        $this->assertStringContainsString('PHP/' . PHP_VERSION, $ua);
        $this->assertStringContainsString('curl/', $ua);
    }

    public function testUserAgentWithAppInfoName()
    {
        $client = new B2BRouterClient('test_api_key', [
            'app_info' => ['name' => 'MyApp'],
        ]);

        $this->assertStringEndsWith(' MyApp', $client->getUserAgent());
    }

    public function testUserAgentWithAppInfoNameAndVersion()
    {
        $client = new B2BRouterClient('test_api_key', [
            'app_info' => ['name' => 'MyApp', 'version' => '1.0.3'],
        ]);

        $this->assertStringEndsWith(' MyApp/1.0.3', $client->getUserAgent());
    }

    public function testUserAgentWithFullAppInfo()
    {
        $client = new B2BRouterClient('test_api_key', [
            'app_info' => [
                'name' => 'MyApp',
                'version' => '1.0.3',
                'url' => 'https://shop.example.com',
            ],
        ]);

        $this->assertStringEndsWith(' MyApp/1.0.3 (https://shop.example.com)', $client->getUserAgent());
    }

    public function testAppInfoRequiresName()
    {
        $this->expectException(\InvalidArgumentException::class);
        new B2BRouterClient('test_api_key', [
            'app_info' => ['version' => '1.0.0'],
        ]);
    }

    public function testAppInfoRejectsEmptyName()
    {
        $this->expectException(\InvalidArgumentException::class);
        new B2BRouterClient('test_api_key', [
            'app_info' => ['name' => ''],
        ]);
    }

    public function testAppInfoRejectsNonArray()
    {
        $this->expectException(\InvalidArgumentException::class);
        new B2BRouterClient('test_api_key', [
            'app_info' => 'MyApp',
        ]);
    }

    public function testUserAgentHeaderSentInRequests()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'invoice' => ['id' => 'inv_1'],
        ]));

        $client->invoices->retrieve('inv_1');

        $request = $mockHttp->getLastRequest();
        $this->assertArrayHasKey('User-Agent', $request['headers']);
        $this->assertStringStartsWith('B2BRouter-PHP/', $request['headers']['User-Agent']);
    }

    public function testUserAgentHeaderSentInBinaryRequests()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse([
            'body' => '%PDF-1.4 mock',
            'status' => 200,
            'headers' => ['Content-Type' => 'application/pdf'],
        ]);

        $client->invoices->downloadPdf('inv_1');

        $request = $mockHttp->getLastRequest();
        $this->assertArrayHasKey('User-Agent', $request['headers']);
        $this->assertStringStartsWith('B2BRouter-PHP/', $request['headers']['User-Agent']);
    }

    public function testUserAgentHeaderIncludesAppInfoOnTheWire()
    {
        [$client, $mockHttp] = $this->createTestClient([
            'app_info' => ['name' => 'MyApp', 'version' => '2.0.0'],
        ]);

        $mockHttp->addResponse($this->mockResponse([
            'invoice' => ['id' => 'inv_1'],
        ]));

        $client->invoices->retrieve('inv_1');

        $request = $mockHttp->getLastRequest();
        $this->assertStringEndsWith(' MyApp/2.0.0', $request['headers']['User-Agent']);
    }
}
