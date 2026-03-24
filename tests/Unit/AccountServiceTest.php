<?php

namespace B2BRouter\Tests\Unit;

use B2BRouter\Collection;
use B2BRouter\Tests\TestCase;

class AccountServiceTest extends TestCase
{
    public function testListAccounts()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'accounts' => [
                ['id' => 1, 'name' => 'Account One'],
                ['id' => 2, 'name' => 'Account Two']
            ],
            'meta' => [
                'total_count' => 50,
                'offset' => 0,
                'limit' => 2
            ]
        ]));

        $result = $client->accounts->all(['limit' => 2, 'offset' => 0]);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);

        // Verify request
        $request = $mockHttp->getLastRequest();
        $this->assertEquals('GET', $request['method']);
        $this->assertStringContainsString('/accounts', $request['url']);
        $this->assertStringContainsString('limit=2', $request['url']);
    }

    public function testListAccountsWithQuery()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'accounts' => [],
            'meta' => ['total_count' => 0, 'offset' => 0, 'limit' => 25]
        ]));

        $client->accounts->all(['query' => 'country=es AND tin_scheme=9920']);

        $request = $mockHttp->getLastRequest();
        $this->assertStringContainsString('query=', $request['url']);
    }

    public function testCreateAccount()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'account' => [
                'id' => 1,
                'name' => 'New Account',
                'tin_value' => 'ESB12345678'
            ]
        ]));

        $result = $client->accounts->create([
            'account' => [
                'name' => 'New Account',
                'tin_value' => 'ESB12345678',
                'email' => 'test@example.com',
                'phone' => '+34600000000',
                'address' => 'Calle Test 1',
                'city' => 'Madrid',
                'postalcode' => '28001',
                'province' => 'Madrid'
            ]
        ]);

        $this->assertEquals(1, $result['id']);
        $this->assertEquals('New Account', $result['name']);

        // Verify request
        $request = $mockHttp->getLastRequest();
        $this->assertEquals('POST', $request['method']);
        $this->assertStringContainsString('/accounts', $request['url']);
    }

    public function testCreateAccountRequiresAccountParameter()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "account" parameter is required');

        [$client, $mockHttp] = $this->createTestClient();
        $client->accounts->create([]);
    }

    public function testRetrieveAccount()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'account' => [
                'id' => 1,
                'name' => 'Test Account',
                'tin_value' => 'ESB12345678'
            ]
        ]));

        $result = $client->accounts->retrieve(1);

        $this->assertEquals(1, $result['id']);
        $this->assertEquals('Test Account', $result['name']);

        // Verify request
        $request = $mockHttp->getLastRequest();
        $this->assertEquals('GET', $request['method']);
        $this->assertStringContainsString('/accounts/1', $request['url']);
    }

    public function testUpdateAccount()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'account' => [
                'id' => 1,
                'name' => 'Updated Account'
            ]
        ]));

        $result = $client->accounts->update(1, [
            'account' => ['name' => 'Updated Account']
        ]);

        $this->assertEquals('Updated Account', $result['name']);

        // Verify request
        $request = $mockHttp->getLastRequest();
        $this->assertEquals('PUT', $request['method']);
        $this->assertStringContainsString('/accounts/1', $request['url']);
    }

    public function testUpdateAccountRequiresAccountParameter()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "account" parameter is required');

        [$client, $mockHttp] = $this->createTestClient();
        $client->accounts->update(1, []);
    }

    public function testDeleteAccount()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'account' => [
                'id' => 1,
                'deleted' => true
            ]
        ]));

        $result = $client->accounts->delete(1);

        $this->assertEquals(1, $result['id']);
        $this->assertTrue($result['deleted']);

        // Verify request
        $request = $mockHttp->getLastRequest();
        $this->assertEquals('DELETE', $request['method']);
        $this->assertStringContainsString('/accounts/1', $request['url']);
    }

    public function testUnarchiveAccount()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'account' => [
                'id' => 1,
                'archived' => false
            ]
        ]));

        $result = $client->accounts->unarchive(1);

        $this->assertEquals(1, $result['id']);
        $this->assertFalse($result['archived']);

        // Verify request
        $request = $mockHttp->getLastRequest();
        $this->assertEquals('POST', $request['method']);
        $this->assertStringContainsString('/accounts/1/unarchive', $request['url']);
    }

    public function testUploadLogo()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'account' => [
                'id' => 1,
                'has_logo' => true
            ]
        ]));

        $logoData = 'fake-binary-png-data';
        $result = $client->accounts->uploadLogo(1, $logoData);

        $this->assertEquals(1, $result['id']);
        $this->assertTrue($result['has_logo']);

        // Verify request
        $request = $mockHttp->getLastRequest();
        $this->assertEquals('POST', $request['method']);
        $this->assertStringContainsString('/accounts/1/logo', $request['url']);
        $this->assertEquals('application/octet-stream', $request['headers']['Content-Type']);
    }

    public function testDeleteLogo()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'account' => [
                'id' => 1,
                'has_logo' => false
            ]
        ]));

        $result = $client->accounts->deleteLogo(1);

        $this->assertEquals(1, $result['id']);
        $this->assertFalse($result['has_logo']);

        // Verify request
        $request = $mockHttp->getLastRequest();
        $this->assertEquals('DELETE', $request['method']);
        $this->assertStringContainsString('/accounts/1/logo', $request['url']);
    }
}
