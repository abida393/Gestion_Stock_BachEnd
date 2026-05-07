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
        Schema::create('previsions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('produit_id');
            $table->string('periode');
            $table->float('quantite_predite');
            $table->float('confiance');
            $table->float('eoq')->nullable();
            $table->float('score_anomalie')->nullable();
            $table->timestamps();

            $table->foreign('produit_id')->references('id')->on('produits')->onDelete('cascade');
        });

        Schema::create('historique_ventes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('produit_id');
            $table->date('date');
            $table->integer('quantite');
            $table->timestamps();

            $table->foreign('produit_id')->references('id')->on('produits')->onDelete('cascade');
        });

        Schema::create('rapports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('utilisateur_id');
            $table->string('type');
            $table->string('chemin_fichier');
            $table->timestamp('genere_le');
            $table->timestamps();

            $table->foreign('utilisateur_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('utilisateur_id');
            $table->string('message');
            $table->string('type');
            $table->boolean('lu')->default(false);
            $table->timestamps();

            $table->foreign('utilisateur_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('rapports');
        Schema::dropIfExists('historique_ventes');
        Schema::dropIfExists('previsions');
    }
};
