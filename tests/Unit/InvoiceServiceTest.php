<?php

namespace B2BRouter\Tests\Unit;

use B2BRouter\Collection;
use B2BRouter\Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    public function testCreateInvoice()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'invoice' => [
                'id' => 'inv_12345',
                'number' => 'INV-2025-001',
                'total' => 1000.00
            ]
        ]));

        $result = $client->invoices->create('test-account', [
            'invoice' => [
                'number' => 'INV-2025-001',
                'total' => 1000.00
            ]
        ]);

        $this->assertEquals('inv_12345', $result['id']);
        $this->assertEquals('INV-2025-001', $result['number']);

        // Verify request
        $request = $mockHttp->getLastRequest();
        $this->assertEquals('POST', $request['method']);
        $this->assertStringContainsString('/accounts/test-account/invoices', $request['url']);
        $this->assertEquals('test_api_key_12345', $request['headers']['X-B2B-API-Key']);
    }

    public function testCreateInvoiceRequiresInvoiceParameter()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "invoice" parameter is required');

        [$client, $mockHttp] = $this->createTestClient();
        $client->invoices->create('test-account', []);
    }

    public function testRetrieveInvoice()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'invoice' => [
                'id' => 'inv_12345',
                'number' => 'INV-2025-001',
                'status' => 'sent'
            ]
        ]));

        $result = $client->invoices->retrieve('inv_12345');

        $this->assertEquals('inv_12345', $result['id']);
        $this->assertEquals('sent', $result['status']);

        // Verify request
        $request = $mockHttp->getLastRequest();
        $this->assertEquals('GET', $request['method']);
        $this->assertStringContainsString('/invoices/inv_12345', $request['url']);
    }

    public function testRetrieveInvoiceWithParameters()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'invoice' => ['id' => 'inv_12345']
        ]));

        $client->invoices->retrieve('inv_12345', [
            'include' => 'buyer,lines',
            'ack' => true
        ]);

        $request = $mockHttp->getLastRequest();
        $this->assertStringContainsString('include=buyer%2Clines', $request['url']);
        $this->assertStringContainsString('ack=1', $request['url']);
    }

    public function testUpdateInvoice()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'invoice' => [
                'id' => 'inv_12345',
                'notes' => 'Updated notes'
            ]
        ]));

        $result = $client->invoices->update('inv_12345', [
            'invoice' => [
                'notes' => 'Updated notes'
            ]
        ]);

        $this->assertEquals('Updated notes', $result['notes']);

        // Verify request
        $request = $mockHttp->getLastRequest();
        $this->assertEquals('PUT', $request['method']);
        $this->assertStringContainsString('/invoices/inv_12345', $request['url']);
    }

    public function testUpdateInvoiceRequiresInvoiceParameter()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "invoice" parameter is required');

        [$client, $mockHttp] = $this->createTestClient();
        $client->invoices->update('inv_12345', []);
    }

    public function testDeleteInvoice()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'invoice' => [
                'id' => 'inv_12345',
                'deleted' => true
            ]
        ]));

        $result = $client->invoices->delete('inv_12345');

        $this->assertEquals('inv_12345', $result['id']);
        $this->assertTrue($result['deleted']);

        // Verify request
        $request = $mockHttp->getLastRequest();
        $this->assertEquals('DELETE', $request['method']);
        $this->assertStringContainsString('/invoices/inv_12345', $request['url']);
    }

    public function testListInvoices()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'invoices' => [
                ['id' => 'inv_1', 'number' => 'INV-001'],
                ['id' => 'inv_2', 'number' => 'INV-002']
            ],
            'meta' => [
                'total' => 100,
                'offset' => 0,
                'limit' => 2
            ]
        ]));

        $result = $client->invoices->all('test-account', [
            'limit' => 2,
            'offset' => 0
        ]);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertEquals(100, $result->getTotal());
        $this->assertTrue($result->hasMore());

        // Verify request
        $request = $mockHttp->getLastRequest();
        $this->assertEquals('GET', $request['method']);
        $this->assertStringContainsString('/accounts/test-account/invoices', $request['url']);
        $this->assertStringContainsString('limit=2', $request['url']);
        $this->assertStringContainsString('offset=0', $request['url']);
    }

    public function testListInvoicesWithFilters()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'invoices' => [],
            'meta' => ['total' => 0, 'offset' => 0, 'limit' => 25]
        ]));

        $client->invoices->all('test-account', [
            'date_from' => '2025-01-01',
            'date_to' => '2025-12-31',
            'type' => 'issued',
            'sent' => true
        ]);

        $request = $mockHttp->getLastRequest();
        $this->assertStringContainsString('date_from=2025-01-01', $request['url']);
        $this->assertStringContainsString('date_to=2025-12-31', $request['url']);
        $this->assertStringContainsString('type=issued', $request['url']);
        $this->assertStringContainsString('sent=1', $request['url']);
    }

    public function testImportInvoice()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'invoice' => ['id' => 'inv_12345']
        ]));

        $result = $client->invoices->import('test-account', [
            'file' => 'base64data'
        ]);

        $this->assertEquals('inv_12345', $result['id']);

        // Verify request
        $request = $mockHttp->getLastRequest();
        $this->assertEquals('POST', $request['method']);
        $this->assertStringContainsString('/accounts/test-account/invoices/import', $request['url']);
    }

    public function testMarkAsInvoice()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'invoice' => ['id' => 'inv_12345', 'status' => 'sent']
        ]));

        $result = $client->invoices->markAs('inv_12345', [
            'status' => 'sent'
        ]);

        $this->assertEquals('sent', $result['status']);

        // Verify request
        $request = $mockHttp->getLastRequest();
        $this->assertEquals('POST', $request['method']);
        $this->assertStringContainsString('/invoices/inv_12345/mark_as', $request['url']);
    }

    public function testValidateInvoice()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'valid' => true,
            'errors' => []
        ]));

        $result = $client->invoices->validate('inv_12345');

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);

        // Verify request
        $request = $mockHttp->getLastRequest();
        $this->assertEquals('GET', $request['method']);
        $this->assertStringContainsString('/invoices/inv_12345/validate', $request['url']);
    }

    public function testSendInvoice()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'sent' => true
        ]));

        $result = $client->invoices->send('inv_12345');

        $this->assertTrue($result['sent']);

        // Verify request
        $request = $mockHttp->getLastRequest();
        $this->assertEquals('POST', $request['method']);
        $this->assertStringContainsString('/invoices/send_invoice/inv_12345', $request['url']);
    }

    public function testAcknowledgeInvoice()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'acknowledged' => true
        ]));

        $result = $client->invoices->acknowledge('inv_12345', [
            'ack' => true
        ]);

        $this->assertTrue($result['acknowledged']);

        // Verify request
        $request = $mockHttp->getLastRequest();
        $this->assertEquals('POST', $request['method']);
        $this->assertStringContainsString('/invoices/inv_12345/ack', $request['url']);
    }
}
