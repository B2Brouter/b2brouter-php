<?php
/**
 * Example: Contact management
 *
 * Demonstrates listing, creating, retrieving, updating, and deleting contacts.
 */

require_once __DIR__ . '/bootstrap.php';

use B2BRouter\B2BRouterClient;
use B2BRouter\Exception\ApiErrorException;

checkRequiredEnv();

$client = new B2BRouterClient(env('B2B_API_KEY'), [
    'api_version' => env('B2B_API_VERSION', '2026-03-02'),
    'api_base' => env('B2B_API_BASE'),
]);

$accountId = env('B2B_ACCOUNT_ID');

// ============================================================
// 1. List contacts
// ============================================================

exampleHeader('1. List Contacts');

try {
    $contacts = $client->contacts->all($accountId, ['limit' => 10]);

    echo "Found {$contacts->count()} contacts";
    if ($contacts->getTotal()) {
        echo " (Total: {$contacts->getTotal()})";
    }
    echo "\n\n";

    foreach ($contacts as $contact) {
        printf("  ID: %-10s Name: %-30s TIN: %s\n",
            $contact['id'],
            $contact['name'] ?? 'N/A',
            $contact['tin_value'] ?? 'N/A'
        );
    }
} catch (ApiErrorException $e) {
    echo "Error listing contacts: {$e->getMessage()}\n";
}

// ============================================================
// 2. Filter contacts
// ============================================================

exampleHeader('2. Filter Contacts');

try {
    // Search by name
    $contacts = $client->contacts->all($accountId, [
        'name' => 'example',
        'limit' => 5,
    ]);
    echo "Contacts matching 'example': {$contacts->getTotal()}\n";

    // Filter by type
    $clients = $client->contacts->all($accountId, [
        'is_client' => true,
        'limit' => 5,
    ]);
    echo "Client contacts: {$clients->getTotal()}\n";

    $providers = $client->contacts->all($accountId, [
        'is_provider' => true,
        'limit' => 5,
    ]);
    echo "Provider contacts: {$providers->getTotal()}\n";
} catch (ApiErrorException $e) {
    echo "Error filtering contacts: {$e->getMessage()}\n";
}

// ============================================================
// 3. Create, retrieve, update, and delete a contact
// ============================================================

exampleHeader('3. Contact CRUD');

try {
    // Create
    $contact = $client->contacts->create($accountId, [
        'contact' => [
            'name' => 'SDK Test Contact ' . date('His'),
            'email' => 'sdk-test@example.com',
            'tin_value' => 'ESB00000000',
            'country' => 'ES',
            'address' => 'Calle Test 1',
            'city' => 'Madrid',
            'postalcode' => '28001',
            'province' => 'Madrid',
        ]
    ]);
    echo "Created contact: {$contact['id']} ({$contact['name']})\n";

    // Retrieve
    $retrieved = $client->contacts->retrieve($contact['id']);
    echo "Retrieved: {$retrieved['name']} - {$retrieved['email']}\n";

    // Update
    $updated = $client->contacts->update($contact['id'], [
        'contact' => [
            'name' => 'SDK Test Contact Updated',
            'phone' => '+34600000000',
        ]
    ]);
    echo "Updated: {$updated['name']}\n";

    // Delete
    $deleted = $client->contacts->delete($contact['id']);
    echo "Deleted contact: {$deleted['id']}\n";

} catch (ApiErrorException $e) {
    echo "Error: {$e->getMessage()}\n";
    if ($e->getRequestId()) {
        echo "Request ID: {$e->getRequestId()}\n";
    }
}
