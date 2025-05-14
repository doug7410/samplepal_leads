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
        Schema::table('contacts', function (Blueprint $table) {
            // First, add the new office_phone column
            $table->string('office_phone')->nullable();

            // Then rename the existing phone column to cell_phone
            $table->renameColumn('phone', 'cell_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            // Reverse the changes: rename cell_phone back to phone
            $table->renameColumn('cell_phone', 'phone');

            // Drop the office_phone column
            $table->dropColumn('office_phone');
        });
    }
};
