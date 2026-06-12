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
        // For Postgres, changing enum to string often leaves a check constraint
        DB::statement('ALTER TABLE mouvement_stock DROP CONSTRAINT IF EXISTS mouvement_stock_type_check');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No easy way to restore the exact constraint without knowing the previous values
    }
};
