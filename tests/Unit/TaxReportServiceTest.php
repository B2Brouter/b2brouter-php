<?php

namespace B2BRouter\Tests\Unit;

use B2BRouter\Collection;
use B2BRouter\Tests\TestCase;

class TaxReportServiceTest extends TestCase
{
    public function testListTaxReports()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'tax_reports' => [
                [
                    'id' => 'tr_12345',
                    'invoice_id' => 'inv_001',
                    'status' => 'registered'
                ],
                [
                    'id' => 'tr_67890',
                    'invoice_id' => 'inv_002',
                    'status' => 'pending'
                ]
            ],
            'meta' => [
                'total' => 50,
                'offset' => 0,
                'limit' => 2
            ]
        ]));

        $result = $client->taxReports->all('test-account', [
            'limit' => 2,
            'offset' => 0
        ]);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertEquals(50, $result->getTotal());
        $this->assertTrue($result->hasMore());

        // Verify request
        $request = $mockHttp->getLastRequest();
        $this->assertEquals('GET', $request['method']);
        $this->assertStringContainsString('/accounts/test-account/tax_reports', $request['url']);
        $this->assertStringContainsString('limit=2', $request['url']);
        $this->assertStringContainsString('offset=0', $request['url']);
    }

    public function testListTaxReportsWithFilters()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'tax_reports' => [],
            'meta' => ['total' => 0, 'offset' => 0, 'limit' => 25]
        ]));

        $client->taxReports->all('test-account', [
            'invoice_id' => 'inv_12345',
            'sent_at_from' => '2025-01-01',
            'updated_at_from' => '2025-01-01'
        ]);

        $request = $mockHttp->getLastRequest();
        $this->assertStringContainsString('invoice_id=inv_12345', $request['url']);
        $this->assertStringContainsString('sent_at_from=2025-01-01', $request['url']);
        $this->assertStringContainsString('updated_at_from=2025-01-01', $request['url']);
    }

    public function testRetrieveTaxReport()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'tax_report' => [
                'id' => 'tr_12345',
                'invoice_id' => 'inv_001',
                'status' => 'registered',
                'qr_code' => 'https://example.com/qr/tr_12345',
                'created_at' => '2025-11-11T10:00:00Z'
            ]
        ]));

        $result = $client->taxReports->retrieve('tr_12345');

        $this->assertEquals('tr_12345', $result['id']);
        $this->assertEquals('inv_001', $result['invoice_id']);
        $this->assertEquals('registered', $result['status']);

        // Verify request
        $request = $mockHttp->getLastRequest();
        $this->assertEquals('GET', $request['method']);
        $this->assertStringContainsString('/tax_reports/tr_12345', $request['url']);
    }

    public function testRetrieveTaxReportWithParameters()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'tax_report' => ['id' => 'tr_12345']
        ]));

        $client->taxReports->retrieve('tr_12345', [
            'include' => 'invoice'
        ]);

        $request = $mockHttp->getLastRequest();
        $this->assertStringContainsString('include=invoice', $request['url']);
    }

    public function testCreateTaxReport()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'tax_report' => [
                'id' => 'tr_new123',
                'type' => 'Verifactu',
                'state' => 'processing',
                'invoice_number' => '2025-001',
                'invoice_date' => '2025-04-03',
                'customer_party_name' => 'Test Customer S.L.',
                'tax_inclusive_amount' => 121.0,
                'qr' => 'base64encodedqrcode...'
            ]
        ]));

        $result = $client->taxReports->create('test-account', [
            'tax_report' => [
                'type' => 'Verifactu',
                'invoice_date' => '2025-04-03',
                'invoice_number' => '2025-001',
                'description' => 'Test invoice',
                'customer_party_tax_id' => 'B12345678',
                'customer_party_country' => 'es',
                'customer_party_name' => 'Test Customer S.L.',
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

        $this->assertEquals('tr_new123', $result['id']);
        $this->assertEquals('Verifactu', $result['type']);
        $this->assertEquals('processing', $result['state']);
        $this->assertEquals('2025-001', $result['invoice_number']);

        // Verify request
        $request = $mockHttp->getLastRequest();
        $this->assertEquals('POST', $request['method']);
        $this->assertStringContainsString('/accounts/test-account/tax_reports', $request['url']);
        $this->assertArrayHasKey('body', $request);
    }

    public function testCreateTaxReportRequiresTaxReportParameter()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "tax_report" parameter is required');

        $client->taxReports->create('test-account', [
            'invoice_number' => '2025-001'
        ]);
    }

    public function testDownloadTaxReport()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?><RegistroAlta><Cabecera>...</Cabecera></RegistroAlta>';

        // Create a response array directly (not using mockResponse helper which encodes to JSON)
        $mockHttp->addResponse([
            'body' => $xmlContent,
            'status' => 200,
            'headers' => ['Content-Type' => 'application/xml']
        ]);

        $result = $client->taxReports->download('tr_12345');

        $this->assertStringContainsString('<?xml version="1.0"', $result);
        $this->assertStringContainsString('RegistroAlta', $result);

        // Verify request
        $request = $mockHttp->getLastRequest();
        $this->assertEquals('GET', $request['method']);
        $this->assertStringContainsString('/tax_reports/tr_12345/download', $request['url']);
    }

    public function testUpdateTaxReport()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'tax_report' => [
                'id' => 'tr_12345',
                'type' => 'Verifactu',
                'state' => 'processing',
                'invoice_number' => '2025-001-CORRECTED',
                'correction' => true
            ]
        ]));

        $result = $client->taxReports->update('tr_12345', [
            'tax_report' => [
                'invoice_number' => '2025-001-CORRECTED',
                'description' => 'Corrected description',
                'tax_inclusive_amount' => 133.1
            ]
        ]);

        $this->assertEquals('tr_12345', $result['id']);
        $this->assertEquals('2025-001-CORRECTED', $result['invoice_number']);
        $this->assertTrue($result['correction']);

        // Verify request
        $request = $mockHttp->getLastRequest();
        $this->assertEquals('PATCH', $request['method']);
        $this->assertStringContainsString('/tax_reports/tr_12345', $request['url']);
    }

    public function testUpdateTaxReportRequiresTaxReportParameter()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "tax_report" parameter is required');

        $client->taxReports->update('tr_12345', [
            'invoice_number' => '2025-001'
        ]);
    }

    public function testDeleteTaxReport()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'tax_report' => [
                'id' => 'tr_annul123',
                'type' => 'Verifactu',
                'state' => 'processing',
                'annullation' => true,
                'original_tax_report_id' => 'tr_12345'
            ]
        ]));

        $result = $client->taxReports->delete('tr_12345');

        $this->assertEquals('tr_annul123', $result['id']);
        $this->assertTrue($result['annullation']);
        $this->assertEquals('tr_12345', $result['original_tax_report_id']);

        // Verify request
        $request = $mockHttp->getLastRequest();
        $this->assertEquals('DELETE', $request['method']);
        $this->assertStringContainsString('/tax_reports/tr_12345', $request['url']);
    }
}
