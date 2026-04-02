<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('produit_fournisseur', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('produit_id');
            $table->bigInteger('fournisseur_id');
            $table->integer('delai_livraison_jours');
            $table->decimal('prix_unitaire', 10, 2);
            $table->timestamps();

            $table->foreign('produit_id')->references('id')->on('produits')->onDelete('cascade');
            $table->foreign('fournisseur_id')->references('id')->on('fournisseurs')->onDelete('cascade');
            $table->unique(['produit_id', 'fournisseur_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produit_fournisseur');
    }
};
