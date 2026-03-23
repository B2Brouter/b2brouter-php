<?php
/**
 * VeriFactu Tax Report Settings Setup Example
 *
 * This example demonstrates how to configure tax report settings for VeriFactu.
 * You must configure tax report settings before creating tax reports.
 *
 * Key concepts:
 * - Check if VeriFactu is already configured for the account
 * - Create VeriFactu settings if not present
 * - Update settings if needed
 * - View and manage existing settings
 *
 * Setup:
 *   1. Copy .env.example to .env
 *   2. Add your B2B_API_KEY and B2B_ACCOUNT_ID
 *   3. Run: php examples/tax_report_setup.php
 */

require_once __DIR__ . '/bootstrap.php';

use B2BRouter\B2BRouterClient;
use B2BRouter\Exception\ApiErrorException;

// Check required environment variables
checkRequiredEnv();

// Initialize the client
$client = new B2BRouterClient(env('B2B_API_KEY'), [
    'api_version' => env('B2B_API_VERSION', '2025-10-13'),
    'api_base' => env('B2B_API_BASE'),
]);

$accountId = env('B2B_ACCOUNT_ID');

try {
    echo "=== VeriFactu Tax Report Settings Setup ===\n\n";

    // ============================================
    // Step 1: Check if VeriFactu is already configured
    // ============================================
    echo "Checking if VeriFactu is already configured...\n\n";

    $verifactuConfigured = false;
    $existingSettings = null;

    try {
        // Try to retrieve VeriFactu settings
        $existingSettings = $client->taxReportSettings->retrieve($accountId, 'verifactu');
        $verifactuConfigured = true;

        echo "✓ VeriFactu is already configured for this account!\n";
        echo "  Code: {$existingSettings['code']}\n";
        echo "  Start date: " . ($existingSettings['start_date'] ?? 'Not set') . "\n";
        echo "  Auto generate: " . ($existingSettings['auto_generate'] ? 'Yes' : 'No') . "\n";
        echo "  Auto send: " . ($existingSettings['auto_send'] ? 'Yes' : 'No') . "\n";
        echo "  Special regime key: " . ($existingSettings['special_regime_key'] ?? 'Not set') . "\n";
        echo "  Reason VAT exempt: " . ($existingSettings['reason_vat_exempt'] ?? 'Not set') . "\n";
        echo "  Reason no subject: " . ($existingSettings['reason_no_subject'] ?? 'Not set') . "\n";
        echo "  Credit note code: " . ($existingSettings['credit_note_code'] ?? 'Not set') . "\n";
        echo "\n";

    } catch (ApiErrorException $e) {
        if ($e->getHttpStatus() === 404) {
            echo "✗ VeriFactu is not configured for this account.\n";
            echo "  Let's create the configuration...\n\n";
        } else {
            throw $e; // Re-throw if it's a different error
        }
    }

    // ============================================
    // Step 2: Create VeriFactu settings if not configured
    // ============================================
    if (!$verifactuConfigured) {
        echo "Creating VeriFactu tax report settings...\n\n";

        // Get today's date for start_date
        $startDate = date('Y-m-d');

        $newSettings = $client->taxReportSettings->create($accountId, [
            'tax_report_setting' => [
                // Required: Tax report code (must be lowercase)
                'code' => 'verifactu',

                // Required: Start date for VeriFactu compliance
                'start_date' => $startDate,

                // Optional: Auto-generate tax reports from invoices (default: false)
                'auto_generate' => true,

                // Optional: Auto-send tax reports to AEAT (default: false)
                'auto_send' => true,

                // Optional: Default special regime key (default: '01' - General regime)
                // See verifactu.md for all possible values
                'special_regime_key' => '01',

                // Optional: Default reason for VAT exemption
                // Required if you issue exempt invoices (E1-E6)
                // See verifactu.md for all possible values
                'reason_vat_exempt' => 'E1',

                // Optional: Default reason for non-subject operations
                // Required if you issue non-subject invoices (N1-N2)
                // See verifactu.md for all possible values
                'reason_no_subject' => 'N1',

                // Optional: Default credit note type code
                // Required if you issue credit notes (R1-R5)
                // See verifactu.md for all possible values
                'credit_note_code' => 'R1',
            ]
        ]);

        echo "✓ VeriFactu settings created successfully!\n";
        echo "  Code: {$newSettings['code']}\n";
        echo "  Start date: {$newSettings['start_date']}\n";
        echo "  Auto generate: " . ($newSettings['auto_generate'] ? 'Yes' : 'No') . "\n";
        echo "  Auto send: " . ($newSettings['auto_send'] ? 'Yes' : 'No') . "\n";
        echo "\n";

        $existingSettings = $newSettings;
    }

    // ============================================
    // Step 3: List all tax report settings
    // ============================================
    echo "Listing all tax report settings for this account...\n\n";

    $allSettings = $client->taxReportSettings->all($accountId);

    echo "Found {$allSettings->count()} tax report setting(s):\n\n";

    foreach ($allSettings as $setting) {
        echo "  • {$setting['code']}\n";
        if (isset($setting['start_date'])) {
            echo "    Start date: {$setting['start_date']}\n";
        }
        if (isset($setting['auto_generate'])) {
            echo "    Auto generate: " . ($setting['auto_generate'] ? 'Yes' : 'No') . "\n";
        }
        if (isset($setting['auto_send'])) {
            echo "    Auto send: " . ($setting['auto_send'] ? 'Yes' : 'No') . "\n";
        }
        echo "\n";
    }

    // ============================================
    // Step 4: Update settings (optional example)
    // ============================================
    echo "Example: Updating VeriFactu settings...\n";
    echo "Note: This is just a demonstration. Uncomment to actually update.\n\n";

    // Uncomment the following to actually update settings
    /*
    $updatedSettings = $client->taxReportSettings->update($accountId, 'verifactu', [
        'tax_report_setting' => [
            'special_regime_key' => '04', // Change to gold investment regime
            'reason_vat_exempt' => 'E2',  // Change exemption reason
        ]
    ]);

    echo "✓ Settings updated successfully!\n";
    echo "  New special regime key: {$updatedSettings['special_regime_key']}\n";
    echo "  New VAT exempt reason: {$updatedSettings['reason_vat_exempt']}\n";
    */

    // ============================================
    // Tips and Best Practices
    // ============================================
    echo "=== Tips for VeriFactu Settings ===\n";
    echo "1. Configure settings BEFORE creating your first tax report\n";
    echo "2. Set 'start_date' to the date you want to start VeriFactu compliance\n";
    echo "3. Use 'auto_generate' = true if you're using the Invoice API\n";
    echo "4. Use 'auto_send' = true to automatically send tax reports to AEAT\n";
    echo "5. Set default codes for your most common invoice types\n";
    echo "6. You can override default codes per tax report if needed\n";
    echo "7. Refer to verifactu.md for all possible code values\n\n";

    echo "=== VeriFactu Setup Complete ===\n";
    echo "Your account is now ready to create VeriFactu tax reports!\n";
    echo "Next: Run 'php examples/verifactu_tax_report.php' to create a tax report.\n\n";

} catch (ApiErrorException $e) {
    echo "\nAPI Error: {$e->getMessage()}\n";
    echo "HTTP Status: {$e->getHttpStatus()}\n";

    if ($e->getRequestId()) {
        echo "Request ID: {$e->getRequestId()}\n";
    }

    if ($e->getJsonBody()) {
        echo "\nError details:\n";
        echo json_encode($e->getJsonBody(), JSON_PRETTY_PRINT) . "\n";
    }
} catch (\Exception $e) {
    echo "\nError: {$e->getMessage()}\n";
}
