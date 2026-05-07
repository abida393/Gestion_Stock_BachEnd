<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    \DB::enableQueryLog();
    App\Models\Fournisseur::first()->produits()->get();
} catch (\Exception $e) {
    echo "ERROR MESSAGE:\n" . $e->getMessage() . "\n\n";
    print_r(\DB::getQueryLog());
}
