<?php

namespace B2BRouter\Tests\Unit;

use B2BRouter\Exception\ApiConnectionException;
use B2BRouter\HttpClient\CurlClient;
use PHPUnit\Framework\TestCase;

/**
 * Retry behaviour of CurlClient against sockets on 127.0.0.1 only
 * (no external network).
 *
 * A listening socket that never accepts still completes the TCP handshake, so
 * cURL sends the whole request and then times out waiting for the reply. That
 * is the case where the server may already have processed the request.
 */
class CurlClientRetryTest extends TestCase
{
    private $server;

    protected function tearDown(): void
    {
        if (is_resource($this->server)) {
            fclose($this->server);
        }
    }

    public function testPostIsNotResentAfterTimeout()
    {
        $url = $this->silentServerUrl();
        $client = new CurlClient(2, 1);

        $this->expectRequestFailure($client, 'POST', $url);

        $this->assertSame(1, $this->countConnections(), 'POST must be sent once');
    }

    public function testPutAndDeleteAreNotResentAfterTimeout()
    {
        foreach (['PUT', 'DELETE'] as $method) {
            $url = $this->silentServerUrl();
            $client = new CurlClient(1, 1);

            $this->expectRequestFailure($client, $method, $url);

            $this->assertSame(1, $this->countConnections(), "$method must be sent once");
            fclose($this->server);
        }
    }

    public function testGetIsStillRetriedAfterTimeout()
    {
        $url = $this->silentServerUrl();
        $client = new CurlClient(1, 1);

        $this->expectRequestFailure($client, 'GET', $url);

        $this->assertSame(2, $this->countConnections(), 'GET should be retried');
    }

    public function testPostIsStillRetriedWhenConnectionIsRefused()
    {
        // Nothing listens on this port, so the request never leaves the client.
        $server = stream_socket_server('tcp://127.0.0.1:0');
        $port = (int) substr(strrchr(stream_socket_get_name($server, false), ':'), 1);
        fclose($server);

        $client = new CurlClient(1, 300);
        $start = microtime(true);

        try {
            $client->request('POST', "http://127.0.0.1:$port/", [], '{}', 1);
            $this->fail('Expected ApiConnectionException');
        } catch (ApiConnectionException $e) {
            $this->assertStringContainsString('cURL error 7', $e->getMessage());
        }

        // One retry means one backoff sleep of retryDelay (300 ms).
        $this->assertGreaterThanOrEqual(0.3, microtime(true) - $start, 'POST should be retried');
    }

    private function silentServerUrl()
    {
        $this->server = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        $this->assertNotFalse($this->server, $errstr);

        return 'http://' . stream_socket_get_name($this->server, false) . '/invoices';
    }

    private function expectRequestFailure(CurlClient $client, $method, $url)
    {
        try {
            $client->request($method, $url, ['Content-Type' => 'application/json'], ['invoice' => []], 1);
            $this->fail('Expected ApiConnectionException');
        } catch (ApiConnectionException $e) {
            $this->assertStringContainsString('cURL error 28', $e->getMessage());
        }
    }

    private function countConnections()
    {
        stream_set_blocking($this->server, false);
        $count = 0;
        while (@stream_socket_accept($this->server, 0) !== false) {
            $count++;
        }

        return $count;
    }
}
