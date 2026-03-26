<?php

namespace B2BRouter\Service;

use B2BRouter\ApiResource;
use B2BRouter\Collection;

/**
 * Service for managing contacts.
 *
 * @since API 2025-10-13
 */
class ContactService extends ApiResource
{
    /**
     * List all contacts for an account.
     *
     * @param string $account The account identifier
     * @param array $params Query parameters:
     *   - offset: Pagination offset (default: 0)
     *   - limit: Number of items per page (default: 25, max: 500)
     *   - name: Search by customer name, address, or VAT code
     *   - is_client: Filter by client status (boolean)
     *   - is_provider: Filter by provider status (boolean)
     *   - integration_code: Filter by integration code (exact match)
     * @return Collection A paginated collection of contacts
     * @throws \B2BRouter\Exception\ApiErrorException
     * @since API 2025-10-13
     */
    public function all($account, array $params = [])
    {
        $path = "/accounts/{$account}/contacts";
        $response = $this->request('GET', $path, $params);

        $contacts = isset($response['contacts']) ? $response['contacts'] : [];
        $meta = isset($response['meta']) ? $response['meta'] : null;

        return new Collection($contacts, $meta);
    }

    /**
     * Create a contact.
     *
     * @param string $account The account identifier
     * @param array $params Contact data:
     *   - contact: (required) Contact object with fields:
     *     - name: (required) Contact name
     *     - email: (required) Email address
     *     - tin_value: Tax identification number
     *     - tin_scheme: TIN scheme
     *     - country: Country code
     *     - address, city, postalcode, province: Address fields
     *     - phone, website: Contact info
     *     - language: Language code (default: 'en')
     *     - is_client: Boolean (default: true)
     *     - is_provider: Boolean (default: true in API 2026-03-02+)
     *     - integration_code: ERP internal code
     *     - payment_method: Payment method code
     *     - terms: Payment terms
     *     - parent_id: Parent contact ID (creates organizational unit)
     * @param array $options Request options
     * @return array The created contact
     * @throws \InvalidArgumentException If required parameters are missing
     * @throws \B2BRouter\Exception\ApiErrorException
     * @since API 2025-10-13
     */
    public function create($account, array $params, array $options = [])
    {
        if (!isset($params['contact'])) {
            throw new \InvalidArgumentException('The "contact" parameter is required');
        }

        $path = "/accounts/{$account}/contacts";
        $response = $this->request('POST', $path, $params, $options);

        return isset($response['contact']) ? $response['contact'] : $response;
    }

    /**
     * Retrieve a contact.
     *
     * @param string $id The contact ID
     * @param array $params Query parameters
     * @return array The contact data
     * @throws \B2BRouter\Exception\ApiErrorException
     * @since API 2025-10-13
     */
    public function retrieve($id, array $params = [])
    {
        $path = "/contacts/{$id}";
        $response = $this->request('GET', $path, $params);

        return isset($response['contact']) ? $response['contact'] : $response;
    }

    /**
     * Update a contact.
     *
     * @param string $id The contact ID
     * @param array $params Update data:
     *   - contact: (required) Contact object with fields to update
     * @param array $options Request options
     * @return array The updated contact
     * @throws \InvalidArgumentException If required parameters are missing
     * @throws \B2BRouter\Exception\ApiErrorException
     * @since API 2025-10-13
     */
    public function update($id, array $params, array $options = [])
    {
        if (!isset($params['contact'])) {
            throw new \InvalidArgumentException('The "contact" parameter is required');
        }

        $path = "/contacts/{$id}";
        $response = $this->request('PUT', $path, $params, $options);

        return isset($response['contact']) ? $response['contact'] : $response;
    }

    /**
     * Delete a contact.
     *
     * @param string $id The contact ID
     * @return array The deleted contact
     * @throws \B2BRouter\Exception\ApiErrorException
     * @since API 2025-10-13
     */
    public function delete($id)
    {
        $path = "/contacts/{$id}";
        $response = $this->request('DELETE', $path);

        return isset($response['contact']) ? $response['contact'] : $response;
    }
}
