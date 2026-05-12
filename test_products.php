<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$request = Illuminate\Http\Request::create('/api/v1/products', 'GET');
$controller = new App\Http\Controllers\Api\ProductController();
$response = $controller->index($request)->toResponse($request);
echo $response->getContent();
