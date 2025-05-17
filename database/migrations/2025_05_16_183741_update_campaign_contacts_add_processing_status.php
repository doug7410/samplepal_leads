<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip PostgreSQL-specific statements for SQLite during tests
        if (config('database.default') !== 'sqlite') {
            // For PostgreSQL, we need to modify the enum type
            DB::statement('ALTER TABLE campaign_contacts DROP CONSTRAINT IF EXISTS campaign_contacts_status_check');
            DB::statement("ALTER TABLE campaign_contacts ADD CONSTRAINT campaign_contacts_status_check CHECK (status::text = ANY (ARRAY['pending'::character varying, 'processing'::character varying, 'sent'::character varying, 'delivered'::character varying, 'opened'::character varying, 'clicked'::character varying, 'responded'::character varying, 'bounced'::character varying, 'failed'::character varying]::text[]))");
        } else {
            // For SQLite, do nothing as it doesn't support constraints in the same way
            // The validation should happen at the application level
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Skip PostgreSQL-specific statements for SQLite during tests
        if (config('database.default') !== 'sqlite') {
            // Restore original constraint without 'processing' status
            DB::statement('ALTER TABLE campaign_contacts DROP CONSTRAINT IF EXISTS campaign_contacts_status_check');
            DB::statement("ALTER TABLE campaign_contacts ADD CONSTRAINT campaign_contacts_status_check CHECK (status::text = ANY (ARRAY['pending'::character varying, 'sent'::character varying, 'delivered'::character varying, 'opened'::character varying, 'clicked'::character varying, 'responded'::character varying, 'bounced'::character varying, 'failed'::character varying]::text[]))");
        }
    }
};
