<?php

namespace B2BRouter\Exception;

/**
 * Base exception for API errors.
 */
class ApiErrorException extends \Exception implements ExceptionInterface
{
    protected $httpStatus;
    protected $httpBody;
    protected $jsonBody;
    protected $httpHeaders;
    protected $requestId;

    /**
     * @param string $message
     * @param int|null $httpStatus
     * @param string|null $httpBody
     * @param array|null $jsonBody
     * @param array|null $httpHeaders
     */
    public function __construct(
        $message,
        $httpStatus = null,
        $httpBody = null,
        $jsonBody = null,
        $httpHeaders = null
    ) {
        parent::__construct($message);
        $this->httpStatus = $httpStatus;
        $this->httpBody = $httpBody;
        $this->jsonBody = $jsonBody;
        $this->httpHeaders = $httpHeaders;

        if (isset($httpHeaders['X-Request-Id'])) {
            $this->requestId = $httpHeaders['X-Request-Id'];
        }
    }

    /**
     * @return int|null
     */
    public function getHttpStatus()
    {
        return $this->httpStatus;
    }

    /**
     * @return string|null
     */
    public function getHttpBody()
    {
        return $this->httpBody;
    }

    /**
     * @return array|null
     */
    public function getJsonBody()
    {
        return $this->jsonBody;
    }

    /**
     * @return array|null
     */
    public function getHttpHeaders()
    {
        return $this->httpHeaders;
    }

    /**
     * @return string|null
     */
    public function getRequestId()
    {
        return $this->requestId;
    }
}
