<?php

namespace B2BRouter\Tests\Unit;

use B2BRouter\Collection;
use B2BRouter\Tests\TestCase;

class ContactServiceTest extends TestCase
{
    public function testListContacts()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'contacts' => [
                ['id' => 1, 'name' => 'Contact One'],
                ['id' => 2, 'name' => 'Contact Two']
            ],
            'meta' => [
                'total_count' => 50,
                'offset' => 0,
                'limit' => 2
            ]
        ]));

        $result = $client->contacts->all('test-account', ['limit' => 2]);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);

        // Verify request
        $request = $mockHttp->getLastRequest();
        $this->assertEquals('GET', $request['method']);
        $this->assertStringContainsString('/accounts/test-account/contacts', $request['url']);
        $this->assertStringContainsString('limit=2', $request['url']);
    }

    public function testListContactsWithFilters()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'contacts' => [],
            'meta' => ['total_count' => 0, 'offset' => 0, 'limit' => 25]
        ]));

        $client->contacts->all('test-account', [
            'name' => 'Acme',
            'is_client' => true,
            'is_provider' => false
        ]);

        $request = $mockHttp->getLastRequest();
        $this->assertStringContainsString('name=Acme', $request['url']);
        $this->assertStringContainsString('is_client=1', $request['url']);
        $this->assertStringContainsString('is_provider=0', $request['url']);
    }

    public function testCreateContact()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'contact' => [
                'id' => 1,
                'name' => 'New Contact',
                'tin_value' => 'ESB12345678'
            ]
        ]));

        $result = $client->contacts->create('test-account', [
            'contact' => [
                'name' => 'New Contact',
                'email' => 'contact@example.com',
                'tin_value' => 'ESB12345678',
                'country' => 'ES'
            ]
        ]);

        $this->assertEquals(1, $result['id']);
        $this->assertEquals('New Contact', $result['name']);

        // Verify request
        $request = $mockHttp->getLastRequest();
        $this->assertEquals('POST', $request['method']);
        $this->assertStringContainsString('/accounts/test-account/contacts', $request['url']);
    }

    public function testCreateContactRequiresContactParameter()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "contact" parameter is required');

        [$client, $mockHttp] = $this->createTestClient();
        $client->contacts->create('test-account', []);
    }

    public function testRetrieveContact()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'contact' => [
                'id' => 1,
                'name' => 'Test Contact',
                'email' => 'contact@example.com'
            ]
        ]));

        $result = $client->contacts->retrieve(1);

        $this->assertEquals(1, $result['id']);
        $this->assertEquals('Test Contact', $result['name']);

        // Verify request
        $request = $mockHttp->getLastRequest();
        $this->assertEquals('GET', $request['method']);
        $this->assertStringContainsString('/contacts/1', $request['url']);
    }

    public function testUpdateContact()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'contact' => [
                'id' => 1,
                'name' => 'Updated Contact'
            ]
        ]));

        $result = $client->contacts->update(1, [
            'contact' => ['name' => 'Updated Contact']
        ]);

        $this->assertEquals('Updated Contact', $result['name']);

        // Verify request
        $request = $mockHttp->getLastRequest();
        $this->assertEquals('PUT', $request['method']);
        $this->assertStringContainsString('/contacts/1', $request['url']);
    }

    public function testUpdateContactRequiresContactParameter()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "contact" parameter is required');

        [$client, $mockHttp] = $this->createTestClient();
        $client->contacts->update(1, []);
    }

    public function testDeleteContact()
    {
        [$client, $mockHttp] = $this->createTestClient();

        $mockHttp->addResponse($this->mockResponse([
            'contact' => [
                'id' => 1,
                'name' => 'Deleted Contact'
            ]
        ]));

        $result = $client->contacts->delete(1);

        $this->assertEquals(1, $result['id']);

        // Verify request
        $request = $mockHttp->getLastRequest();
        $this->assertEquals('DELETE', $request['method']);
        $this->assertStringContainsString('/contacts/1', $request['url']);
    }
}
