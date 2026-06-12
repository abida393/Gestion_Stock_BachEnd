<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->date('date_prevue_livraison')->nullable()->after('date_commande');
            $table->date('date_reception_reelle')->nullable()->after('date_prevue_livraison');
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->dropColumn(['date_prevue_livraison', 'date_reception_reelle']);
        });
    }
};
