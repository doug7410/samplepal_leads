<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            // For SQLite, we have to recreate the table with the new enum
            Schema::table('campaigns', function (Blueprint $table) {
                $table->string('type')->default('contact')->after('user_id');
            });
        } else {
            // For PostgreSQL/MySQL
            Schema::table('campaigns', function (Blueprint $table) {
                $table->string('type')->default('contact')->after('user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};