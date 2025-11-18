<?php

namespace B2BRouter\Tests\Mock;

use B2BRouter\HttpClient\ClientInterface;
use B2BRouter\Exception\ApiConnectionException;

/**
 * Mock HTTP client for testing.
 */
class MockHttpClient implements ClientInterface
{
    private $responses = [];
    private $requestHistory = [];
    private $callIndex = 0;

    /**
     * Add a response to the queue.
     *
     * @param array|callable $response Response array or callable
     */
    public function addResponse($response)
    {
        $this->responses[] = $response;
    }

    /**
     * @inheritDoc
     */
    public function request($method, $url, $headers, $body, $timeout)
    {
        // Record the request
        $this->requestHistory[] = [
            'method' => $method,
            'url' => $url,
            'headers' => $headers,
            'body' => $body,
            'timeout' => $timeout
        ];

        // Check if we have a response queued
        if (empty($this->responses)) {
            throw new \RuntimeException('No mock response available');
        }

        $response = array_shift($this->responses);

        // If response is callable, call it with request details
        if (is_callable($response)) {
            $response = $response($method, $url, $headers, $body, $timeout);
        }

        // If response is an exception, throw it
        if ($response instanceof \Exception) {
            throw $response;
        }

        $this->callIndex++;

        return $response;
    }

    /**
     * Get all recorded requests.
     *
     * @return array
     */
    public function getRequestHistory()
    {
        return $this->requestHistory;
    }

    /**
     * Get the last request.
     *
     * @return array|null
     */
    public function getLastRequest()
    {
        return end($this->requestHistory) ?: null;
    }

    /**
     * Get request count.
     *
     * @return int
     */
    public function getRequestCount()
    {
        return count($this->requestHistory);
    }

    /**
     * Reset the mock client.
     */
    public function reset()
    {
        $this->responses = [];
        $this->requestHistory = [];
        $this->callIndex = 0;
    }
}
