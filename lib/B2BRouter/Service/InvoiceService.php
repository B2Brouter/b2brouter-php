<?php

namespace B2BRouter\Service;

use B2BRouter\ApiResource;
use B2BRouter\Collection;

/**
 * Service for managing invoices.
 */
class InvoiceService extends ApiResource
{
    /**
     * Create an invoice.
     *
     * @param string $account The account identifier
     * @param array $params Invoice data:
     *   - invoice: (required) Invoice object
     *   - send_after_import: (optional) Send after import flag
     *   - ack: (optional) Acknowledgement flag
     * @param array $options Request options
     * @return array The created invoice
     * @throws \B2BRouter\Exception\ApiErrorException
     */
    public function create($account, array $params, array $options = [])
    {
        if (!isset($params['invoice'])) {
            throw new \InvalidArgumentException('The "invoice" parameter is required');
        }

        $path = "/accounts/{$account}/invoices";
        $response = $this->request('POST', $path, $params, $options);

        return isset($response['invoice']) ? $response['invoice'] : $response;
    }

    /**
     * Retrieve an invoice.
     *
     * @param string $id The invoice ID
     * @param array $params Query parameters:
     *   - include: Additional data to include
     *   - disposition: Disposition type
     *   - ack: Acknowledgement flag
     * @return array The invoice data
     * @throws \B2BRouter\Exception\ApiErrorException
     */
    public function retrieve($id, array $params = [])
    {
        $path = "/invoices/{$id}";
        $response = $this->request('GET', $path, $params);

        return isset($response['invoice']) ? $response['invoice'] : $response;
    }

    /**
     * Update an invoice.
     *
     * @param string $id The invoice ID
     * @param array $params Update data:
     *   - invoice: (required) Invoice object with fields to update
     * @param array $options Request options
     * @return array The updated invoice
     * @throws \B2BRouter\Exception\ApiErrorException
     */
    public function update($id, array $params, array $options = [])
    {
        if (!isset($params['invoice'])) {
            throw new \InvalidArgumentException('The "invoice" parameter is required');
        }

        $path = "/invoices/{$id}";
        $response = $this->request('PUT', $path, $params, $options);

        return isset($response['invoice']) ? $response['invoice'] : $response;
    }

    /**
     * Delete an invoice.
     *
     * @param string $id The invoice ID
     * @return array The deleted invoice
     * @throws \B2BRouter\Exception\ApiErrorException
     */
    public function delete($id)
    {
        $path = "/invoices/{$id}";
        $response = $this->request('DELETE', $path);

        return isset($response['invoice']) ? $response['invoice'] : $response;
    }

    /**
     * List all invoices for an account.
     *
     * @param string $account The account identifier
     * @param array $params Query parameters:
     *   - offset: Pagination offset (default: 0)
     *   - limit: Number of items per page (default: 25, max: 500)
     *   - type: Filter by invoice type
     *   - ack: Filter by acknowledgement status
     *   - date_from: Filter by date from
     *   - date_to: Filter by date to
     *   - due_date_from: Filter by due date from
     *   - due_date_to: Filter by due date to
     *   - number: Filter by invoice number
     *   - taxcode: Filter by tax code
     *   - new: Filter by new status
     *   - sending: Filter by sending status
     *   - error: Filter by error status
     *   - sent: Filter by sent status
     *   - refused: Filter by refused status
     *   - closed: Filter by closed status
     *   - registered: Filter by registered status
     *   - accepted: Filter by accepted status
     *   - allegedly_paid: Filter by allegedly paid status
     *   - received_invoice_received: Filter received invoices
     *   - received_invoice_paid: Filter paid received invoices
     *   - received_invoice_ocr_failed: Filter OCR failed invoices
     *   - received_invoice_exclude_offices: Exclude offices
     *   - from_net: Filter by from net
     * @return Collection A paginated collection of invoices
     * @throws \B2BRouter\Exception\ApiErrorException
     */
    public function all($account, array $params = [])
    {
        $path = "/accounts/{$account}/invoices";
        $response = $this->request('GET', $path, $params);

        $invoices = isset($response['invoices']) ? $response['invoices'] : [];
        $meta = isset($response['meta']) ? $response['meta'] : null;

        return new Collection($invoices, $meta);
    }

    /**
     * Import an invoice.
     *
     * @param string $account The account identifier
     * @param array $params Import data
     * @param array $options Request options
     * @return array The imported invoice
     * @throws \B2BRouter\Exception\ApiErrorException
     */
    public function import($account, array $params, array $options = [])
    {
        $path = "/accounts/{$account}/invoices/import";
        $response = $this->request('POST', $path, $params, $options);

        return isset($response['invoice']) ? $response['invoice'] : $response;
    }

    /**
     * Mark an invoice with a specific status.
     *
     * @param string $id The invoice ID
     * @param array $params Mark parameters
     * @param array $options Request options
     * @return array The updated invoice
     * @throws \B2BRouter\Exception\ApiErrorException
     */
    public function markAs($id, array $params, array $options = [])
    {
        $path = "/invoices/{$id}/mark_as";
        $response = $this->request('POST', $path, $params, $options);

        return isset($response['invoice']) ? $response['invoice'] : $response;
    }

    /**
     * Validate an invoice.
     *
     * @param string $id The invoice ID
     * @param array $params Validation parameters
     * @return array Validation result
     * @throws \B2BRouter\Exception\ApiErrorException
     */
    public function validate($id, array $params = [])
    {
        $path = "/invoices/{$id}/validate";
        return $this->request('GET', $path, $params);
    }

    /**
     * Send an invoice.
     *
     * @param string $id The invoice ID
     * @param array $params Send parameters
     * @param array $options Request options
     * @return array The result
     * @throws \B2BRouter\Exception\ApiErrorException
     */
    public function send($id, array $params = [], array $options = [])
    {
        $path = "/invoices/send_invoice/{$id}";
        return $this->request('POST', $path, $params, $options);
    }

    /**
     * Acknowledge an invoice.
     *
     * @param string $id The invoice ID
     * @param array $params Acknowledgement parameters
     * @param array $options Request options
     * @return array The result
     * @throws \B2BRouter\Exception\ApiErrorException
     */
    public function acknowledge($id, array $params, array $options = [])
    {
        $path = "/invoices/{$id}/ack";
        return $this->request('POST', $path, $params, $options);
    }
}
