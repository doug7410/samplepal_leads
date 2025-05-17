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
        Schema::table('campaigns', function (Blueprint $table) {
            // Drop the existing enum constraint
            // For SQLite, we need to recreate the table since it doesn't support modifying enum constraints
            // For PostgreSQL or MySQL, you'd use DB facade to alter the enum
            // Since SQLite is used for testing, we'll do this in a way that works with SQLite

            // First, modify the column to allow all possible values
            $table->string('status')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            // Convert back to enum (though in SQLite this is effectively a no-op)
            $table->string('status')->change();
        });
    }
};