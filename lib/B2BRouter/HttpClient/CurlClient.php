<?php

namespace B2BRouter\HttpClient;

use B2BRouter\Exception\ApiConnectionException;

/**
 * HTTP client using cURL with automatic retry logic.
 */
class CurlClient implements ClientInterface
{
    private $maxRetries = 3;
    private $retryDelay = 1000; // milliseconds

    /**
     * @param int $maxRetries Maximum number of retries for failed requests
     * @param int $retryDelay Initial delay between retries in milliseconds
     */
    public function __construct($maxRetries = 3, $retryDelay = 1000)
    {
        $this->maxRetries = $maxRetries;
        $this->retryDelay = $retryDelay;
    }

    /**
     * @inheritDoc
     */
    public function request($method, $url, $headers, $body, $timeout = 80)
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt <= $this->maxRetries) {
            try {
                return $this->executeRequest($method, $url, $headers, $body, $timeout);
            } catch (ApiConnectionException $e) {
                $lastException = $e;
                $attempt++;

                if ($attempt > $this->maxRetries) {
                    throw $e;
                }

                // Exponential backoff
                $delay = $this->retryDelay * pow(2, $attempt - 1);
                usleep($delay * 1000); // Convert to microseconds
            }
        }

        throw $lastException;
    }

    /**
     * Execute a single HTTP request.
     *
     * @param string $method
     * @param string $url
     * @param array $headers
     * @param array|string|null $body
     * @param int $timeout
     * @return array
     * @throws ApiConnectionException
     */
    private function executeRequest($method, $url, $headers, $body, $timeout)
    {
        $curl = curl_init();

        // Set URL
        curl_setopt($curl, CURLOPT_URL, $url);

        // Set method
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($method));

        // Set headers
        $curlHeaders = [];
        foreach ($headers as $name => $value) {
            $curlHeaders[] = "$name: $value";
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, $curlHeaders);

        // Set body
        if ($body !== null) {
            if (is_array($body)) {
                $body = json_encode($body);
            }
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }

        // Other options
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_HEADER, true);

        // Execute request
        $response = curl_exec($curl);
        $errorNo = curl_errno($curl);
        $error = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);

        curl_close($curl);

        // Handle connection errors
        if ($errorNo !== 0) {
            throw new ApiConnectionException(
                "cURL error {$errorNo}: {$error}",
                0,
                null,
                null,
                null
            );
        }

        // Parse response
        $headerString = substr($response, 0, $headerSize);
        $responseBody = substr($response, $headerSize);

        // Parse headers
        $responseHeaders = [];
        $headerLines = explode("\r\n", $headerString);
        foreach ($headerLines as $line) {
            $parts = explode(': ', $line, 2);
            if (count($parts) === 2) {
                $responseHeaders[$parts[0]] = $parts[1];
            }
        }

        return [
            'body' => $responseBody,
            'status' => $httpCode,
            'headers' => $responseHeaders
        ];
    }
}
