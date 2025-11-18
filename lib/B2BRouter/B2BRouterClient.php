<?php

namespace B2BRouter;

use B2BRouter\HttpClient\ClientInterface;
use B2BRouter\HttpClient\CurlClient;
use B2BRouter\Service\InvoiceService;
use B2BRouter\Service\TaxReportSettingService;
use B2BRouter\Service\TaxReportService;

/**
 * Main client for the B2BRouter API.
 *
 * @property InvoiceService $invoices
 * @property TaxReportSettingService $taxReportSettings
 * @property TaxReportService $taxReports
 */
class B2BRouterClient
{
    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $apiBase = 'https://api-staging.b2brouter.net';

    /**
     * @var string
     */
    private $apiVersion = '2025-10-13';

    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var int
     */
    private $timeout = 80;

    /**
     * @var array
     */
    private $services = [];

    /**
     * Create a new B2BRouter client.
     *
     * @param string $apiKey The API key for authentication
     * @param array $options Optional configuration:
     *   - api_base: Override the default API base URL
     *   - api_version: Override the default API version
     *   - http_client: Custom HTTP client implementation
     *   - timeout: Request timeout in seconds (default: 80)
     *   - max_retries: Maximum number of retries (default: 3)
     */
    public function __construct($apiKey, array $options = [])
    {
        if (empty($apiKey)) {
            throw new \InvalidArgumentException('API key cannot be empty');
        }

        $this->apiKey = $apiKey;

        if (isset($options['api_base'])) {
            $this->apiBase = rtrim($options['api_base'], '/');
        }

        if (isset($options['api_version'])) {
            $this->apiVersion = $options['api_version'];
        }

        if (isset($options['timeout'])) {
            $this->timeout = $options['timeout'];
        }

        if (isset($options['http_client'])) {
            $this->httpClient = $options['http_client'];
        } else {
            $maxRetries = isset($options['max_retries']) ? $options['max_retries'] : 3;
            $this->httpClient = new CurlClient($maxRetries);
        }
    }

    /**
     * Get the API key.
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * Get the API base URL.
     *
     * @return string
     */
    public function getApiBase()
    {
        return $this->apiBase;
    }

    /**
     * Get the API version.
     *
     * @return string
     */
    public function getApiVersion()
    {
        return $this->apiVersion;
    }

    /**
     * Get the HTTP client.
     *
     * @return ClientInterface
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * Get the request timeout.
     *
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * Get a service instance.
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        $serviceClass = $this->getServiceClass($name);

        if (!isset($this->services[$name])) {
            $this->services[$name] = new $serviceClass($this);
        }

        return $this->services[$name];
    }

    /**
     * Get the service class name.
     *
     * @param string $name
     * @return string
     */
    private function getServiceClass($name)
    {
        $services = [
            'invoices' => Service\InvoiceService::class,
            'taxReportSettings' => Service\TaxReportSettingService::class,
            'taxReports' => Service\TaxReportService::class,
        ];

        if (!isset($services[$name])) {
            throw new \InvalidArgumentException("Unknown service: {$name}");
        }

        return $services[$name];
    }
}
