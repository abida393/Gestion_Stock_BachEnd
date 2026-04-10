<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop foreign key and column from users table
        if (Schema::hasColumn('users', 'role_id')) {
            Schema::table('users', function (Blueprint $table) {
                // Ignore errors if foreign key doesn't exist
                try {
                    $table->dropForeign(['role_id']);
                } catch (\Exception $e) {}
                $table->dropColumn('role_id');
            });
        }

        // Drop the custom tables
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }

    public function down(): void
    {
        // No down migration
    }
};
