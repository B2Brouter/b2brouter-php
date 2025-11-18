<?php

namespace B2BRouter\Service;

use B2BRouter\ApiResource;
use B2BRouter\Collection;

/**
 * Service for managing tax reports.
 */
class TaxReportService extends ApiResource
{
    /**
     * List all tax reports for an account.
     *
     * @param string $account The account identifier
     * @param array $params Query parameters:
     *   - offset: Pagination offset (default: 0)
     *   - limit: Number of items per page (default: 25, max: 500)
     *   - invoice_id: Filter by invoice ID
     *   - sent_at_from: Filter by sent at from date
     *   - updated_at_from: Filter by updated at from date
     * @return Collection A paginated collection of tax reports
     * @throws \B2BRouter\Exception\ApiErrorException
     */
    public function all($account, array $params = [])
    {
        $path = "/accounts/{$account}/tax_reports";
        $response = $this->request('GET', $path, $params);

        $taxReports = isset($response['tax_reports']) ? $response['tax_reports'] : [];
        $meta = isset($response['meta']) ? $response['meta'] : null;

        return new Collection($taxReports, $meta);
    }

    /**
     * Retrieve a tax report.
     *
     * @param string $id The tax report ID
     * @param array $params Query parameters
     * @return array The tax report data
     * @throws \B2BRouter\Exception\ApiErrorException
     */
    public function retrieve($id, array $params = [])
    {
        $path = "/tax_reports/{$id}";
        $response = $this->request('GET', $path, $params);

        return isset($response['tax_report']) ? $response['tax_report'] : $response;
    }

    /**
     * Create a tax report.
     *
     * @param string $account The account identifier
     * @param array $params The tax report parameters:
     *   - tax_report: The tax report data (required)
     *     - type: 'Verifactu' or 'TicketBai' (required)
     *     - invoice_date: Invoice date (required)
     *     - invoice_number: Invoice number (required)
     *     - description: Description (required)
     *     - customer_party_name: Customer name (required)
     *     - customer_party_tax_id: Customer tax ID (required)
     *     - customer_party_country: Customer country (required)
     *     - tax_inclusive_amount: Total amount including tax (required)
     *     - tax_amount: Total tax amount (required)
     *     - invoice_type_code: Invoice type code (required)
     *     - currency: Currency code (required)
     *     - tax_breakdowns: Array of tax breakdowns (required)
     *     - tax_report_lines: Array of tax report lines (for TicketBAI)
     * @param array $options Additional request options
     * @return array The created tax report
     * @throws \InvalidArgumentException If required parameters are missing
     * @throws \B2BRouter\Exception\ApiErrorException
     */
    public function create($account, array $params, array $options = [])
    {
        if (!isset($params['tax_report'])) {
            throw new \InvalidArgumentException('The "tax_report" parameter is required');
        }

        $path = "/accounts/{$account}/tax_reports";
        $response = $this->request('POST', $path, $params, $options);

        return isset($response['tax_report']) ? $response['tax_report'] : $response;
    }

    /**
     * Download a tax report as XML.
     *
     * @param string $id The tax report ID
     * @return string The XML content
     * @throws \B2BRouter\Exception\ApiErrorException
     */
    public function download($id)
    {
        $path = "/tax_reports/{$id}/download";

        // Make direct request to get raw response body
        $url = $this->client->getApiBase() . $path;
        $headers = [
            'X-B2B-API-Key' => $this->client->getApiKey(),
            'X-B2B-API-Version' => $this->client->getApiVersion(),
        ];

        $response = $this->client->getHttpClient()->request(
            'GET',
            $url,
            $headers,
            null,
            $this->client->getTimeout()
        );

        // Check for errors
        if ($response['status'] >= 400) {
            // Use handleResponse to throw appropriate exception
            $this->handleResponse($response);
        }

        // Return raw XML body
        return $response['body'];
    }

    /**
     * Update/correct a tax report (subsanación).
     * If the tax report has already been registered by the Tax Authority,
     * this issues a correction. If not yet sent, it updates the fields.
     *
     * @param string $id The tax report ID
     * @param array $params The tax report parameters:
     *   - tax_report: The tax report data to update (required)
     * @param array $options Additional request options
     * @return array The updated/corrected tax report
     * @throws \InvalidArgumentException If required parameters are missing
     * @throws \B2BRouter\Exception\ApiErrorException
     */
    public function update($id, array $params, array $options = [])
    {
        if (!isset($params['tax_report'])) {
            throw new \InvalidArgumentException('The "tax_report" parameter is required');
        }

        $path = "/tax_reports/{$id}";
        $response = $this->request('PATCH', $path, $params, $options);

        return isset($response['tax_report']) ? $response['tax_report'] : $response;
    }

    /**
     * Delete/annulate a tax report (anulación).
     * Creates an annullation tax report that is sent to the Tax Authority.
     *
     * @param string $id The tax report ID
     * @return array The annullation tax report
     * @throws \B2BRouter\Exception\ApiErrorException
     */
    public function delete($id)
    {
        $path = "/tax_reports/{$id}";
        $response = $this->request('DELETE', $path);

        return isset($response['tax_report']) ? $response['tax_report'] : $response;
    }
}
