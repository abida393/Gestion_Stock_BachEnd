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
        Schema::create('commandes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('fournisseur_id');
            $table->timestamp('date_commande');
            $table->enum('statut', ['en_attente', 'livree', 'annulee'])->default('en_attente');
            $table->decimal('total', 10, 2);
            $table->timestamps();

            $table->foreign('fournisseur_id')->references('id')->on('fournisseurs')->onDelete('cascade');
        });

        Schema::create('ligne_commande', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('commande_id');
            $table->bigInteger('produit_id');
            $table->integer('quantite');
            $table->decimal('prix', 10, 2);
            $table->timestamps();

            $table->foreign('commande_id')->references('id')->on('commandes')->onDelete('cascade');
            $table->foreign('produit_id')->references('id')->on('produits')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ligne_commande');
        Schema::dropIfExists('commandes');
    }
};
