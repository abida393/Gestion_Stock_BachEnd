<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\LLMService;

$llm = new LLMService();
echo "Testing Gemini API...\n";
$response = $llm->generateResponse("Dis bonjour et confirme que tu as accès aux données de stock.", ["test_data" => "Stock OK"]);

echo "\nResponse from Gemini:\n";
echo $response . "\n";
