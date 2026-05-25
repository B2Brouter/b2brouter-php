<?php

namespace B2BRouter;

use B2BRouter\Exception\AuthenticationException;
use B2BRouter\Exception\InvalidRequestException;
use B2BRouter\Exception\PermissionException;
use B2BRouter\Exception\ResourceNotFoundException;
use B2BRouter\Exception\ApiErrorException;

/**
 * Base class for API resources.
 */
abstract class ApiResource
{
    /**
     * @var B2BRouterClient
     */
    protected $client;

    /**
     * @param B2BRouterClient $client
     */
    public function __construct(B2BRouterClient $client)
    {
        $this->client = $client;
    }

    /**
     * Make an API request.
     *
     * @param string $method HTTP method
     * @param string $path API path
     * @param array $params Query parameters or request body
     * @param array $options Additional options
     * @return array Decoded response
     * @throws ApiErrorException
     */
    protected function request($method, $path, $params = [], $options = [])
    {
        $method = strtoupper($method);
        $url = $this->client->getApiBase() . $path;

        // Build headers
        $headers = [
            'X-B2B-API-Key' => $this->client->getApiKey(),
            'X-B2B-API-Version' => $this->client->getApiVersion(),
            'User-Agent' => $this->client->getUserAgent(),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        // Handle query parameters and body
        $body = null;
        if ($method === 'GET' || $method === 'DELETE') {
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
        } else {
            $body = $params;
        }

        // Make request
        $response = $this->client->getHttpClient()->request(
            $method,
            $url,
            $headers,
            $body,
            $this->client->getTimeout()
        );

        // Handle response
        return $this->handleResponse($response);
    }

    /**
     * Handle the API response.
     *
     * @param array $response
     * @return array
     * @throws ApiErrorException
     */
    protected function handleResponse($response)
    {
        $statusCode = $response['status'];
        $body = $response['body'];
        $headers = $response['headers'];

        // Try to decode JSON
        $jsonBody = json_decode($body, true);

        // Handle error responses
        if ($statusCode >= 400) {
            $message = $this->extractErrorMessage($jsonBody, $body);

            switch ($statusCode) {
                case 400:
                case 422:
                    throw new InvalidRequestException($message, $statusCode, $body, $jsonBody, $headers);
                case 401:
                    throw new AuthenticationException($message, $statusCode, $body, $jsonBody, $headers);
                case 403:
                    throw new PermissionException($message, $statusCode, $body, $jsonBody, $headers);
                case 404:
                    throw new ResourceNotFoundException($message, $statusCode, $body, $jsonBody, $headers);
                default:
                    throw new ApiErrorException($message, $statusCode, $body, $jsonBody, $headers);
            }
        }

        return $jsonBody ?: [];
    }

    /**
     * Make an API request for binary content (PDF, XML, etc.).
     *
     * @param string $method HTTP method
     * @param string $path API path
     * @param string $acceptType Accept header value (e.g., 'application/pdf')
     * @param array $params Query parameters
     * @param array $options Additional options
     * @return string Binary content
     * @throws ApiErrorException
     */
    protected function requestBinary($method, $path, $acceptType, $params = [], $options = [])
    {
        $method = strtoupper($method);
        $url = $this->client->getApiBase() . $path;

        // Build headers
        $headers = [
            'X-B2B-API-Key' => $this->client->getApiKey(),
            'X-B2B-API-Version' => $this->client->getApiVersion(),
            'User-Agent' => $this->client->getUserAgent(),
            'Accept' => $acceptType
        ];

        // Add query parameters to URL
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        // Make request
        $response = $this->client->getHttpClient()->request(
            $method,
            $url,
            $headers,
            null,
            $this->client->getTimeout()
        );

        // Handle response
        return $this->handleBinaryResponse($response);
    }

    /**
     * Handle binary API response.
     *
     * @param array $response
     * @return string Binary content
     * @throws ApiErrorException
     */
    protected function handleBinaryResponse($response)
    {
        $statusCode = $response['status'];
        $body = $response['body'];
        $headers = $response['headers'];

        // Handle error responses (still might be JSON)
        if ($statusCode >= 400) {
            // Try to parse as JSON error
            $jsonBody = json_decode($body, true);
            $message = $this->extractErrorMessage($jsonBody, $body);

            switch ($statusCode) {
                case 400:
                case 422:
                    throw new InvalidRequestException($message, $statusCode, $body, $jsonBody, $headers);
                case 401:
                    throw new AuthenticationException($message, $statusCode, $body, $jsonBody, $headers);
                case 403:
                    throw new PermissionException($message, $statusCode, $body, $jsonBody, $headers);
                case 404:
                    throw new ResourceNotFoundException($message, $statusCode, $body, $jsonBody, $headers);
                default:
                    throw new ApiErrorException($message, $statusCode, $body, $jsonBody, $headers);
            }
        }

        // Return raw binary content
        return $body;
    }

    /**
     * Extract error message from response.
     *
     * @param array|null $jsonBody
     * @param string $body
     * @return string
     */
    private function extractErrorMessage($jsonBody, $body)
    {
        if (is_array($jsonBody)) {
            if (isset($jsonBody['error']['message'])) {
                return $jsonBody['error']['message'];
            }
            if (isset($jsonBody['message'])) {
                return $jsonBody['message'];
            }
            if (isset($jsonBody['error'])) {
                return is_string($jsonBody['error']) ? $jsonBody['error'] : json_encode($jsonBody['error']);
            }
        }

        return $body ?: 'Unknown error';
    }
}
