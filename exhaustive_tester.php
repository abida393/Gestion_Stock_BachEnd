<?php

require __DIR__ . '/vendor/autoload.php';
use GuzzleHttp\Client;

$client = new Client([
    'base_uri' => 'http://127.0.0.1:8000/api/v1/',
    'http_errors' => false,
    'headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json']
]);

function logTest($name, $method, $path, $status, $expected = 200) {
    if (is_array($expected)) {
        $ok = in_array($status, $expected);
        $expectedStr = implode("/", $expected);
    } else {
        $ok = $status == $expected;
        $expectedStr = $expected;
    }
    
    $result = $ok ? "✅ PASS" : "❌ FAIL";
    echo sprintf("%-50s | %-6s %-30s | %d (%-3s) | %s\n", $name, $method, $path, $status, $expectedStr, $result);
    if (!$ok) {
        return false;
    }
    return true;
}

echo "=== FINAL EXHAUSTIVE API AUDIT START ===\n";
echo str_repeat("-", 115) . "\n";
echo sprintf("%-50s | %-6s %-30s | %-8s | %-6s\n", "FEATURE", "METHOD", "PATH", "STATUS", "RESULT");
echo str_repeat("-", 115) . "\n";

// 1. AUTH & TOKENS
$adminRes = $client->post('auth/login', ['json' => ['email' => 'admin@stockmanager.com', 'password' => 'password']]);
$adminToken = json_decode($adminRes->getBody(), true)['access_token'] ?? null;

// For RBAC check, we use a different user or we DON'T assign roles to this one.
$userRes = $client->post('auth/login', ['json' => ['email' => 'user@stockmanager.com', 'password' => 'password']]);
$userToken = json_decode($userRes->getBody(), true)['access_token'] ?? null;

$A = ['Authorization' => "Bearer $adminToken"];
$U = ['Authorization' => "Bearer $userToken"];

// AUTHENTICATION
logTest("Login Admin", "POST", "auth/login", $adminRes->getStatusCode(), 200);
logTest("Login User", "POST", "auth/login", $userRes->getStatusCode(), 200);
logTest("Profile (Me)", "GET", "auth/me", $client->get('auth/me', ['headers' => $A])->getStatusCode(), 200);
logTest("Update Profile", "PUT", "auth/me", $client->put('auth/me', ['headers' => $A, 'json' => ['name' => 'Admin Updated']])->getStatusCode(), 200);

// USER MANAGEMENT (Admin Only)
logTest("List Users (Admin)", "GET", "users", $client->get('users', ['headers' => $A])->getStatusCode(), 200);
logTest("List Users (User - RBAC)", "GET", "users", $client->get('users', ['headers' => $U])->getStatusCode(), 403);
logTest("Create User", "POST", "users", $client->post('users', ['headers' => $A, 'json' => ['name'=>'Test User New', 'email'=>'test_new_'.uniqid().'@t.com', 'password'=>'password', 'role'=>'user']])->getStatusCode(), [201, 200]);

// ROLE ASSIGNMENT (Testing on User 3 to avoid polluting User 2 used for RBAC checks)
logTest("Assign Role (Admin Only)", "POST", "users/1/roles", $client->post('users/1/roles', ['headers' => $A, 'json' => ['role'=>'admin']])->getStatusCode(), 200);

// RBAC
logTest("List Roles", "GET", "roles", $client->get('roles', ['headers' => $A])->getStatusCode(), 200);
logTest("List Permissions", "GET", "permissions", $client->get('permissions', ['headers' => $A])->getStatusCode(), 200);

// INVENTORY
logTest("List Categories", "GET", "categories", $client->get('categories', ['headers' => $A])->getStatusCode(), 200);
logTest("Create Category", "POST", "categories", $client->post('categories', ['headers' => $A, 'json' => ['nom'=>'Unique Cat '.uniqid(), 'description'=>'Test']])->getStatusCode(), [201, 200]);
logTest("List Products", "GET", "products", $client->get('products', ['headers' => $A])->getStatusCode(), 200);
logTest("Create Product", "POST", "products", $client->post('products', ['headers' => $A, 'json' => ['nom'=>'Unique Product '.uniqid(), 'description'=>'Test Desc', 'categorie_id'=>1, 'prix'=>15.50, 'seuil_min'=>10, 'quantite'=>20]])->getStatusCode(), [201, 200]);
logTest("Low Stock Products", "GET", "products/low-stock", $client->get('products/low-stock', ['headers' => $A])->getStatusCode(), 200);

// SUPPLY CHAIN
logTest("List Suppliers", "GET", "fournisseurs", $client->get('fournisseurs', ['headers' => $A])->getStatusCode(), 200);
logTest("List Orders", "GET", "commandes", $client->get('commandes', ['headers' => $A])->getStatusCode(), 200);

// MOVEMENTS & ALERTS
logTest("List Movements", "GET", "mouvements", $client->get('mouvements', ['headers' => $A])->getStatusCode(), 200);
logTest("List Entries", "GET", "mouvements/entrees", $client->get('mouvements/entrees', ['headers' => $A])->getStatusCode(), 200);
logTest("List Exits", "GET", "mouvements/sorties", $client->get('mouvements/sorties', ['headers' => $A])->getStatusCode(), 200);
logTest("Active Alerts", "GET", "alertes/actives", $client->get('alertes/actives', ['headers' => $A])->getStatusCode(), 200);

// NOTIFICATIONS
logTest("Unread Notifications", "GET", "notifications/unread-count", $client->get('notifications/unread-count', ['headers' => $A])->getStatusCode(), 200);

// REPORTING
logTest("Dashboard", "GET", "dashboard", $client->get('dashboard', ['headers' => $A])->getStatusCode(), 200);
logTest("List Reports", "GET", "rapports", $client->get('rapports', ['headers' => $A])->getStatusCode(), 200);
logTest("Forecasts", "GET", "previsions", $client->get('previsions', ['headers' => $A])->getStatusCode(), 200);

echo str_repeat("-", 115) . "\n";
echo "=== FINAL EXHAUSTIVE API AUDIT COMPLETE ===\n";
