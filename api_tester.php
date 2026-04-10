<?php

require __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

$client = new Client([
    'base_uri' => 'http://127.0.0.1:8000/api/v1/',
    'http_errors' => false,
    'headers' => [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ]
]);

echo "=== API VERIFICATION START ===\n\n";

// 1. Logins
echo "Testing Logins...\n";
$adminResponse = $client->post('auth/login', [
    'json' => ['email' => 'admin@stockmanager.com', 'password' => 'password']
]);
$adminData = json_decode($adminResponse->getBody(), true);
$adminToken = $adminData['access_token'] ?? null;
echo "Admin Login: " . ($adminToken ? "SUCCESS" : "FAILED") . " (" . $adminResponse->getStatusCode() . ")\n";

$userResponse = $client->post('auth/login', [
    'json' => ['email' => 'user@stockmanager.com', 'password' => 'password']
]);
$userData = json_decode($userResponse->getBody(), true);
$userToken = $userData['access_token'] ?? null;
echo "User Login: " . ($userToken ? "SUCCESS" : "FAILED") . " (" . $userResponse->getStatusCode() . ")\n\n";

if (!$adminToken || !$userToken) {
    echo "CRITICAL: Tokens missing. Stopping tests.\n";
    exit(1);
}

// 2. Profile Check
echo "Testing Profiles (/auth/me)...\n";
$adminMe = $client->get('auth/me', ['headers' => ['Authorization' => "Bearer $adminToken"]]);
echo "Admin Me: " . $adminMe->getStatusCode() . " - Roles: " . implode(',', json_decode($adminMe->getBody(), true)['user']['roles'] ?? []) . "\n";

$userMe = $client->get('auth/me', ['headers' => ['Authorization' => "Bearer $userToken"]]);
echo "User Me: " . $userMe->getStatusCode() . " - Roles: " . implode(',', json_decode($userMe->getBody(), true)['user']['roles'] ?? []) . "\n\n";

// 3. User Management (RBAC Test)
echo "Testing User Management (RBAC)...\n";
$adminUsers = $client->get('users', ['headers' => ['Authorization' => "Bearer $adminToken"]]);
echo "Admin GET /users: " . $adminUsers->getStatusCode() . " (Expect 200)\n";

$userUsers = $client->get('users', ['headers' => ['Authorization' => "Bearer $userToken"]]);
echo "User GET /users: " . $userUsers->getStatusCode() . " (Expect 403)\n\n";

// 4. Products & Categories
echo "Testing Inventory...\n";
$adminProducts = $client->get('products', ['headers' => ['Authorization' => "Bearer $adminToken"]]);
echo "Admin GET /products: " . $adminProducts->getStatusCode() . "\n";

$adminCats = $client->get('categories', ['headers' => ['Authorization' => "Bearer $adminToken"]]);
echo "Admin GET /categories: " . $adminCats->getStatusCode() . "\n\n";

// 5. Orders
echo "Testing Orders...\n";
$adminOrders = $client->get('commandes', ['headers' => ['Authorization' => "Bearer $adminToken"]]);
echo "Admin GET /commandes: " . $adminOrders->getStatusCode() . "\n\n";

// 6. Detailed RBAC Check (Post a product image as user - should work as they have access to products)
echo "Testing Detailed Permission (User updates product image)...\n";
// We need a product ID first
$products = json_decode($adminProducts->getBody(), true)['data'] ?? [];
$productId = $products[0]['id'] ?? 1;

$userImage = $client->post("products/$productId/image", [
    'headers' => ['Authorization' => "Bearer $userToken"],
    'json' => ['test' => true] // Just checking auth
]);
echo "User POST /products/$productId/image: " . $userImage->getStatusCode() . " (Expect 200 or 422 if validation fails, not 403)\n\n";

echo "=== API VERIFICATION COMPLETE ===\n";
