<?php

namespace B2BRouter\HttpClient;

/**
 * Interface for HTTP clients.
 */
interface ClientInterface
{
    /**
     * @param string $method The HTTP method
     * @param string $url The URL
     * @param array $headers HTTP headers
     * @param array|string|null $body The request body
     * @param int $timeout The timeout in seconds
     * @return array An array containing the response body, status code, and headers
     */
    public function request($method, $url, $headers, $body, $timeout);
}
