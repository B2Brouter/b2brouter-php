<?php

namespace B2BRouter\HttpClient;

use B2BRouter\Exception\ApiConnectionException;

/**
 * HTTP client using cURL with automatic retry logic.
 *
 * GET requests are retried after any connection error. Other methods are
 * retried only when the connection was never established, because after a
 * timeout or a dropped connection the server may already have processed the
 * request (for example, created the invoice), and sending it again would
 * create a duplicate.
 */
class CurlClient implements ClientInterface
{
    private $maxRetries = 3;
    private $retryDelay = 1000; // milliseconds

    /**
     * cURL errors raised before the request is sent, so any method can be
     * retried safely.
     */
    private const PRE_SEND_ERRORS = [
        5, // CURLE_COULDNT_RESOLVE_PROXY
        6, // CURLE_COULDNT_RESOLVE_HOST
        7, // CURLE_COULDNT_CONNECT
    ];

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
            $errorNo = 0;
            try {
                return $this->executeRequest($method, $url, $headers, $body, $timeout, $errorNo);
            } catch (ApiConnectionException $e) {
                $lastException = $e;
                $attempt++;

                if ($attempt > $this->maxRetries || !$this->isRetryable($method, $errorNo)) {
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
     * Whether a request that failed with the given cURL error can be sent again.
     *
     * @param string $method
     * @param int $errorNo
     * @return bool
     */
    private function isRetryable($method, $errorNo)
    {
        return strtoupper($method) === 'GET' || in_array($errorNo, self::PRE_SEND_ERRORS, true);
    }

    /**
     * Execute a single HTTP request.
     *
     * @param string $method
     * @param string $url
     * @param array $headers
     * @param array|string|null $body
     * @param int $timeout
     * @param int $errorNo Set to the cURL error number (0 on success)
     * @return array
     * @throws ApiConnectionException
     */
    private function executeRequest($method, $url, $headers, $body, $timeout, &$errorNo = 0)
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
