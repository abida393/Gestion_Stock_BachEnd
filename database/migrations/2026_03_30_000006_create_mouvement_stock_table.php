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
        Schema::create('mouvement_stock', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('produit_id');
            $table->bigInteger('utilisateur_id');
            $table->integer('quantite');
            $table->integer('stock_apres');
            $table->text('note')->nullable();
            $table->timestamp('date_mouvement');
            $table->timestamp('cree_le')->useCurrent();
            $table->enum('type', ['entree', 'sortie']);

            $table->foreign('produit_id')->references('id')->on('produits')->onDelete('cascade');
            $table->foreign('utilisateur_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mouvement_stock');
    }
};
