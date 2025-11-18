<?php

namespace B2BRouter\Service;

use B2BRouter\ApiResource;
use B2BRouter\Collection;

/**
 * Service for managing tax report settings.
 */
class TaxReportSettingService extends ApiResource
{
    /**
     * List all tax report settings for an account.
     *
     * @param string $account The account identifier
     * @param array $params Query parameters:
     *   - offset: Pagination offset (default: 0)
     *   - limit: Number of items per page (default: 25, max: 500)
     * @return Collection A paginated collection of tax report settings
     * @throws \B2BRouter\Exception\ApiErrorException
     */
    public function all($account, array $params = [])
    {
        $path = "/accounts/{$account}/tax_report_settings";
        $response = $this->request('GET', $path, $params);

        $settings = isset($response['tax_report_settings']) ? $response['tax_report_settings'] : [];
        $meta = null;

        // Build meta from response if available
        if (isset($response['total_count']) || isset($response['offset']) || isset($response['limit'])) {
            $meta = [
                'total_count' => isset($response['total_count']) ? $response['total_count'] : count($settings),
                'offset' => isset($response['offset']) ? $response['offset'] : 0,
                'limit' => isset($response['limit']) ? $response['limit'] : 25,
            ];
        }

        return new Collection($settings, $meta);
    }

    /**
     * Create a tax report setting.
     *
     * @param string $account The account identifier
     * @param array $params Tax report setting data:
     *   - tax_report_setting: (required) Tax report setting object
     * @param array $options Request options
     * @return array The created tax report setting
     * @throws \B2BRouter\Exception\ApiErrorException
     */
    public function create($account, array $params, array $options = [])
    {
        if (!isset($params['tax_report_setting'])) {
            throw new \InvalidArgumentException('The "tax_report_setting" parameter is required');
        }

        $path = "/accounts/{$account}/tax_report_settings";
        $response = $this->request('POST', $path, $params, $options);

        return isset($response['tax_report_setting']) ? $response['tax_report_setting'] : $response;
    }

    /**
     * Retrieve a tax report setting.
     *
     * @param string $account The account identifier
     * @param string $code The tax authority code
     * @param array $params Query parameters
     * @return array The tax report setting data
     * @throws \B2BRouter\Exception\ApiErrorException
     */
    public function retrieve($account, $code, array $params = [])
    {
        $path = "/accounts/{$account}/tax_report_settings/{$code}";
        $response = $this->request('GET', $path, $params);

        return isset($response['tax_report_setting']) ? $response['tax_report_setting'] : $response;
    }

    /**
     * Update a tax report setting.
     *
     * @param string $account The account identifier
     * @param string $code The tax authority code
     * @param array $params Update data:
     *   - tax_report_setting: (required) Tax report setting object with fields to update
     * @param array $options Request options
     * @return array The updated tax report setting
     * @throws \B2BRouter\Exception\ApiErrorException
     */
    public function update($account, $code, array $params, array $options = [])
    {
        if (!isset($params['tax_report_setting'])) {
            throw new \InvalidArgumentException('The "tax_report_setting" parameter is required');
        }

        $path = "/accounts/{$account}/tax_report_settings/{$code}";
        $response = $this->request('PUT', $path, $params, $options);

        return isset($response['tax_report_setting']) ? $response['tax_report_setting'] : $response;
    }

    /**
     * Delete a tax report setting.
     *
     * @param string $account The account identifier
     * @param string $code The tax authority code
     * @return array The deleted tax report setting
     * @throws \B2BRouter\Exception\ApiErrorException
     */
    public function delete($account, $code)
    {
        $path = "/accounts/{$account}/tax_report_settings/{$code}";
        $response = $this->request('DELETE', $path);

        return isset($response['tax_report_setting']) ? $response['tax_report_setting'] : $response;
    }
}
