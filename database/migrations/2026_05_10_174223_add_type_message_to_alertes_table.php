<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alertes', function (Blueprint $table) {
            $table->string('type', 50)->default('seuil')->after('seuil');
            $table->text('message')->nullable()->after('type');
            $table->float('confiance')->nullable()->after('message');
        });
    }

    public function down(): void
    {
        Schema::table('alertes', function (Blueprint $table) {
            $table->dropColumn(['type', 'message', 'confiance']);
        });
    }
};
