<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Fournisseur;
use App\Models\Produit;

try {
    $f = Fournisseur::find(1);
    $p = Produit::find(1);

    if (!$f) {
        die("Supplier with ID 1 not found.\n");
    }
    if (!$p) {
        die("Product with ID 1 not found.\n");
    }

    echo "Linking Product: {$p->nom} (ID: {$p->id}) to Supplier: {$f->nom} (ID: {$f->id})...\n";

    $f->produits()->syncWithoutDetaching([
        $p->id => [
            'prix_unitaire' => 1500.00,
            'delai_livraison_jours' => 3
        ]
    ]);

    echo "Successfully linked via pivot table: produit_fournisseur\n";

    // Verify
    $verify = $f->fresh()->produits()->find($p->id);
    if ($verify) {
        echo "Verification: Success! Product found in supplier relationship.\n";
        echo "Pivot Data: Price: {$verify->pivot->prix_unitaire}, Lead Time: {$verify->pivot->delai_livraison_jours}\n";
    } else {
        echo "Verification: Failed! Product not found in relationship.\n";
    }

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
