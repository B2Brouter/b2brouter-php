<?php

namespace B2BRouter\Service;

use B2BRouter\ApiResource;
use B2BRouter\Collection;

/**
 * Service for managing accounts.
 *
 * @since API 2025-10-13
 */
class AccountService extends ApiResource
{
    /**
     * List all accounts associated with the API key's group.
     *
     * Note: Unlike other services, accounts are listed at the group level
     * (scoped by API key), so no account ID parameter is needed.
     *
     * @param array $params Query parameters:
     *   - offset: Pagination offset (default: 0)
     *   - limit: Number of items per page (default: 25, max: 500)
     *   - query: Ransack query filter (e.g., 'country=es AND tin_scheme=9920')
     *     Searchable fields: tin_value, tin_scheme, cin_value, cin_scheme, country,
     *     name, identifier, created_at, status
     * @return Collection A paginated collection of accounts
     * @throws \B2BRouter\Exception\ApiErrorException
     * @since API 2025-10-13
     */
    public function all(array $params = [])
    {
        $response = $this->request('GET', '/accounts', $params);

        $accounts = isset($response['accounts']) ? $response['accounts'] : [];
        $meta = isset($response['meta']) ? $response['meta'] : null;

        return new Collection($accounts, $meta);
    }

    /**
     * Create a new account.
     *
     * Note: Only available for eDocSync subscriptions.
     *
     * @param array $params Account data:
     *   - account: (required) Account object with fields:
     *     - name: (required) Account name
     *     - tin_value: (required) Tax identification number
     *     - email: (required) Email address
     *     - phone: (required) Phone number
     *     - address: (required) Primary address
     *     - city: (required) City
     *     - postalcode: (required) Postal code
     *     - province: (required) Province/state
     *     - country: Country code (default: 'es')
     *     - currency: Currency code (default: 'EUR')
     * @param array $options Request options
     * @return array The created account
     * @throws \InvalidArgumentException If required parameters are missing
     * @throws \B2BRouter\Exception\ApiErrorException
     * @since API 2025-10-13
     */
    public function create(array $params, array $options = [])
    {
        if (!isset($params['account'])) {
            throw new \InvalidArgumentException('The "account" parameter is required');
        }

        $response = $this->request('POST', '/accounts', $params, $options);

        return isset($response['account']) ? $response['account'] : $response;
    }

    /**
     * Retrieve an account.
     *
     * @param string $id The account ID
     * @param array $params Query parameters
     * @return array The account data
     * @throws \B2BRouter\Exception\ApiErrorException
     * @since API 2025-10-13
     */
    public function retrieve($id, array $params = [])
    {
        $path = "/accounts/{$id}";
        $response = $this->request('GET', $path, $params);

        return isset($response['account']) ? $response['account'] : $response;
    }

    /**
     * Update an account.
     *
     * Note: TIN (tin_value) cannot be updated.
     *
     * @param string $id The account ID
     * @param array $params Update data:
     *   - account: (required) Account object with fields to update
     * @param array $options Request options
     * @return array The updated account
     * @throws \InvalidArgumentException If required parameters are missing
     * @throws \B2BRouter\Exception\ApiErrorException
     * @since API 2025-10-13
     */
    public function update($id, array $params, array $options = [])
    {
        if (!isset($params['account'])) {
            throw new \InvalidArgumentException('The "account" parameter is required');
        }

        $path = "/accounts/{$id}";
        $response = $this->request('PUT', $path, $params, $options);

        return isset($response['account']) ? $response['account'] : $response;
    }

    /**
     * Delete (archive) an account.
     *
     * Archives the account, or permanently deletes it if destroyable.
     * Throws ApiErrorException with HTTP 409 if the account cannot be deleted.
     *
     * @param string $id The account ID
     * @return array The deleted account (includes 'deleted' boolean field)
     * @throws \B2BRouter\Exception\ApiErrorException On failure, including 409 Conflict
     * @since API 2025-10-13
     */
    public function delete($id)
    {
        $path = "/accounts/{$id}";
        $response = $this->request('DELETE', $path);

        return isset($response['account']) ? $response['account'] : $response;
    }

    /**
     * Unarchive an account.
     *
     * Note: Only available for eDocSync subscriptions.
     * Throws ApiErrorException with HTTP 409 if the account is not archived.
     *
     * @param string $id The account ID
     * @return array The unarchived account
     * @throws \B2BRouter\Exception\ApiErrorException
     * @since API 2025-10-13
     */
    public function unarchive($id)
    {
        $path = "/accounts/{$id}/unarchive";
        $response = $this->request('POST', $path);

        return isset($response['account']) ? $response['account'] : $response;
    }

    /**
     * Upload an account logo.
     *
     * Replaces any existing logo. The data should be the raw binary content
     * of the image file (e.g., from file_get_contents()).
     *
     * @param string $id The account ID
     * @param string $data Raw binary image data
     * @return array The updated account (with has_logo = true)
     * @throws \B2BRouter\Exception\ApiErrorException
     * @since API 2025-10-13
     */
    public function uploadLogo($id, $data)
    {
        $path = "/accounts/{$id}/logo";
        $url = $this->client->getApiBase() . $path;

        $headers = [
            'X-B2B-API-Key' => $this->client->getApiKey(),
            'X-B2B-API-Version' => $this->client->getApiVersion(),
            'Content-Type' => 'application/octet-stream',
            'Accept' => 'application/json',
        ];

        $response = $this->client->getHttpClient()->request(
            'POST',
            $url,
            $headers,
            $data,
            $this->client->getTimeout()
        );

        if ($response['status'] >= 400) {
            $this->handleResponse($response);
        }

        $jsonBody = json_decode($response['body'], true);

        return isset($jsonBody['account']) ? $jsonBody['account'] : ($jsonBody ?: []);
    }

    /**
     * Delete an account logo.
     *
     * @param string $id The account ID
     * @return array The updated account (with has_logo = false)
     * @throws \B2BRouter\Exception\ApiErrorException
     * @since API 2025-10-13
     */
    public function deleteLogo($id)
    {
        $path = "/accounts/{$id}/logo";
        $response = $this->request('DELETE', $path);

        return isset($response['account']) ? $response['account'] : $response;
    }
}
