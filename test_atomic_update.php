<?php

require __DIR__ . '/vendor/autoload.php';
use GuzzleHttp\Client;

$client = new Client([
    'base_uri' => 'http://127.0.0.1:8000/api/v1/',
    'http_errors' => false,
    'headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json']
]);

// 1. Login Admin
$res = $client->post('auth/login', ['json' => ['email' => 'admin@stockmanager.com', 'password' => 'password']]);
$token = json_decode($res->getBody(), true)['access_token'] ?? null;
$headers = ['Authorization' => "Bearer $token"];

echo "--- TESTING ATOMIC UPDATE ---\n";

// 2. Perform Atomic Update on Supplier 1
$payload = [
    'nom' => 'Supplier Atomic Test',
    'email' => 'atomic@supplier.com',
    'telephone' => '123456789',
    'adresse' => '123 Atomic st',
    'produits' => [
        [
            'id' => 1,
            'prix_unitaire' => 1250.50,
            'delai_livraison_jours' => 2
        ],
        [
            'id' => 2,
            'prix_unitaire' => 500.00,
            'delai_livraison_jours' => 5
        ]
    ]
];

$updateRes = $client->put('fournisseurs/1', [
    'headers' => $headers,
    'json' => $payload
]);

echo "Status Code: " . $updateRes->getStatusCode() . "\n";
$body = json_decode($updateRes->getBody(), true);

if ($updateRes->getStatusCode() == 200) {
    echo "Update successful!\n";
    echo "Linked Products Count: " . count($body['data']['produits']) . "\n";
    foreach ($body['data']['produits'] as $p) {
        // Since it's a resource, pivot might be in a pivot key or at base depending on resource implementation
        // Actually, FournisseurResource usually doesn't show pivot unless implemented.
        echo "Product ID: {$p['id']}, Name: {$p['nom']}\n";
    }
} else {
    echo "Update failed.\n";
    print_r($body);
}
