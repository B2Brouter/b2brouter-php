<?php

namespace B2BRouter\Tests\Unit;

use B2BRouter\Collection;
use B2BRouter\Tests\TestCase;

class TaxReportSettingServiceTest extends TestCase
{
    public function testListTaxReportSettings()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'tax_report_settings' => [
                [
                    'code' => 'VERIFACTU',
                    'type' => 'verifactu',
                    'reason_vat_exempt' => 'E1'
                ],
                [
                    'code' => 'TBAI',
                    'type' => 'tbai',
                    'delegation' => 'Bizkaia'
                ]
            ],
            'total_count' => 2,
            'offset' => 0,
            'limit' => 25
        ]));

        $result = $client->taxReportSettings->all('test-account', [
            'limit' => 25,
            'offset' => 0
        ]);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);

        // Verify request
        $request = $mockHttp->getLastRequest();
        $this->assertEquals('GET', $request['method']);
        $this->assertStringContainsString('/accounts/test-account/tax_report_settings', $request['url']);
        $this->assertStringContainsString('limit=25', $request['url']);
        $this->assertStringContainsString('offset=0', $request['url']);
    }

    public function testCreateTaxReportSetting()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'tax_report_setting' => [
                'code' => 'VERIFACTU',
                'type' => 'verifactu',
                'reason_vat_exempt' => 'E1',
                'special_regime_key' => '01',
                'special_regime_key_igic' => '',
                'reason_no_subject' => '',
                'credit_note_code' => 'R1'
            ]
        ]));

        $result = $client->taxReportSettings->create('test-account', [
            'tax_report_setting' => [
                'code' => 'VERIFACTU',
                'type' => 'verifactu',
                'reason_vat_exempt' => 'E1',
                'special_regime_key' => '01',
                'special_regime_key_igic' => '',
                'reason_no_subject' => '',
                'credit_note_code' => 'R1'
            ]
        ]);

        $this->assertEquals('VERIFACTU', $result['code']);
        $this->assertEquals('verifactu', $result['type']);

        // Verify request
        $request = $mockHttp->getLastRequest();
        $this->assertEquals('POST', $request['method']);
        $this->assertStringContainsString('/accounts/test-account/tax_report_settings', $request['url']);
        $this->assertEquals('test_api_key_12345', $request['headers']['X-B2B-API-Key']);
    }

    public function testCreateTaxReportSettingRequiresTaxReportSettingParameter()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "tax_report_setting" parameter is required');

        [$client, $mockHttp] = $this->createTestClient();
        $client->taxReportSettings->create('test-account', []);
    }

    public function testRetrieveTaxReportSetting()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'tax_report_setting' => [
                'code' => 'VERIFACTU',
                'type' => 'verifactu',
                'reason_vat_exempt' => 'E1'
            ]
        ]));

        $result = $client->taxReportSettings->retrieve('test-account', 'VERIFACTU');

        $this->assertEquals('VERIFACTU', $result['code']);
        $this->assertEquals('verifactu', $result['type']);

        // Verify request
        $request = $mockHttp->getLastRequest();
        $this->assertEquals('GET', $request['method']);
        $this->assertStringContainsString('/accounts/test-account/tax_report_settings/VERIFACTU', $request['url']);
    }

    public function testUpdateTaxReportSetting()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'tax_report_setting' => [
                'code' => 'VERIFACTU',
                'type' => 'verifactu',
                'reason_vat_exempt' => 'E2'
            ]
        ]));

        $result = $client->taxReportSettings->update('test-account', 'VERIFACTU', [
            'tax_report_setting' => [
                'reason_vat_exempt' => 'E2'
            ]
        ]);

        $this->assertEquals('E2', $result['reason_vat_exempt']);

        // Verify request
        $request = $mockHttp->getLastRequest();
        $this->assertEquals('PUT', $request['method']);
        $this->assertStringContainsString('/accounts/test-account/tax_report_settings/VERIFACTU', $request['url']);
    }

    public function testUpdateTaxReportSettingRequiresTaxReportSettingParameter()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "tax_report_setting" parameter is required');

        [$client, $mockHttp] = $this->createTestClient();
        $client->taxReportSettings->update('test-account', 'VERIFACTU', []);
    }

    public function testDeleteTaxReportSetting()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'tax_report_setting' => [
                'code' => 'VERIFACTU',
                'type' => 'verifactu',
                'deleted' => true
            ]
        ]));

        $result = $client->taxReportSettings->delete('test-account', 'VERIFACTU');

        $this->assertEquals('VERIFACTU', $result['code']);

        // Verify request
        $request = $mockHttp->getLastRequest();
        $this->assertEquals('DELETE', $request['method']);
        $this->assertStringContainsString('/accounts/test-account/tax_report_settings/VERIFACTU', $request['url']);
    }
}
