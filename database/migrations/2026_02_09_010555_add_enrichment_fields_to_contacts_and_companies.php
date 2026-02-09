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
            $table->string('email_source')->default('scraped');
            $table->boolean('is_enrichment_unusable')->default(false);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->string('website_status')->nullable();
            $table->timestamp('website_checked_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['email_source', 'is_enrichment_unusable']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['website_status', 'website_checked_at']);
        });
    }
};
