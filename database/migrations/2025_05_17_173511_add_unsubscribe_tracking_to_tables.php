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
        // Add unsubscribe fields to contacts table
        Schema::table('contacts', function (Blueprint $table) {
            $table->boolean('has_unsubscribed')->default(false)->after('has_been_contacted');
            $table->timestamp('unsubscribed_at')->nullable()->after('has_unsubscribed');
        });

        // Modify the status enum in campaign_contacts table to include 'unsubscribed'
        // Since SQLite doesn't support ALTER COLUMN directly, we'll use these steps:
        // 1. Rename the current table
        // 2. Create a new table with updated enum
        // 3. Copy data from old to new
        // 4. Drop the old table

        if (DB::connection()->getDriverName() === 'sqlite') {
            // Step 1: Rename the current table
            Schema::rename('campaign_contacts', 'campaign_contacts_old');

            // Step 2: Create a new table with the updated enum
            Schema::create('campaign_contacts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
                $table->foreignId('contact_id')->constrained()->onDelete('cascade');
                $table->enum('status', [
                    'pending', 'processing', 'sent', 'delivered', 'opened',
                    'clicked', 'responded', 'bounced', 'failed', 'unsubscribed',
                ])->default('pending');
                $table->string('message_id')->nullable(); // For tracking
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('opened_at')->nullable();
                $table->timestamp('clicked_at')->nullable();
                $table->timestamp('responded_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->timestamp('unsubscribed_at')->nullable();
                $table->text('failure_reason')->nullable();
                $table->timestamps();

                // Skip creating the unique index, it will be created if not exists in a separate step
            });

            // Step 3: Copy data from old to new table
            DB::statement('INSERT INTO campaign_contacts 
                SELECT id, campaign_id, contact_id, status, message_id, sent_at, delivered_at, 
                       opened_at, clicked_at, responded_at, failed_at, NULL, failure_reason, 
                       created_at, updated_at
                FROM campaign_contacts_old');

            // Step 4: Add unique index if it doesn't exist
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS campaign_contacts_campaign_id_contact_id_unique 
                           ON campaign_contacts (campaign_id, contact_id)');

            // Step 5: Drop the old table
            Schema::dropIfExists('campaign_contacts_old');
        } else {
            // For PostgreSQL (production)
            DB::statement('ALTER TABLE campaign_contacts DROP CONSTRAINT IF EXISTS campaign_contacts_status_check');
            DB::statement("ALTER TABLE campaign_contacts ADD CONSTRAINT campaign_contacts_status_check CHECK (status::text = ANY (ARRAY['pending'::character varying, 'processing'::character varying, 'sent'::character varying, 'delivered'::character varying, 'opened'::character varying, 'clicked'::character varying, 'responded'::character varying, 'bounced'::character varying, 'failed'::character varying, 'cancelled'::character varying, 'unsubscribed'::character varying]::text[]))");

            // Add unsubscribed_at timestamp
            Schema::table('campaign_contacts', function (Blueprint $table) {
                $table->timestamp('unsubscribed_at')->nullable()->after('failed_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove unsubscribe fields from contacts table
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('has_unsubscribed');
            $table->dropColumn('unsubscribed_at');
        });

        // Revert the status enum in campaign_contacts table
        if (DB::connection()->getDriverName() === 'sqlite') {
            // Step 1: Rename the current table
            Schema::rename('campaign_contacts', 'campaign_contacts_old');

            // Step 2: Create a new table with the original enum
            Schema::create('campaign_contacts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
                $table->foreignId('contact_id')->constrained()->onDelete('cascade');
                $table->enum('status', [
                    'pending', 'processing', 'sent', 'delivered', 'opened',
                    'clicked', 'responded', 'bounced', 'failed',
                ])->default('pending');
                $table->string('message_id')->nullable(); // For tracking
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('opened_at')->nullable();
                $table->timestamp('clicked_at')->nullable();
                $table->timestamp('responded_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->text('failure_reason')->nullable();
                $table->timestamps();

                // Skip unique index, it will be added separately
            });

            // Step 3: Copy data from old to new table, excluding unsubscribed records
            DB::statement("INSERT INTO campaign_contacts 
                SELECT id, campaign_id, contact_id, 
                       CASE 
                          WHEN status = 'unsubscribed' THEN 'failed' 
                          ELSE status 
                       END, 
                       message_id, sent_at, delivered_at, opened_at, clicked_at, 
                       responded_at, failed_at, failure_reason, created_at, updated_at
                FROM campaign_contacts_old");

            // Step 4: Add unique index if it doesn't exist
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS campaign_contacts_campaign_id_contact_id_unique 
                           ON campaign_contacts (campaign_id, contact_id)');

            // Step 5: Drop the old table
            Schema::dropIfExists('campaign_contacts_old');
        } else {
            // For PostgreSQL (production)
            DB::statement('ALTER TABLE campaign_contacts DROP CONSTRAINT IF EXISTS campaign_contacts_status_check');
            DB::statement("ALTER TABLE campaign_contacts ADD CONSTRAINT campaign_contacts_status_check CHECK (status::text = ANY (ARRAY['pending'::character varying, 'processing'::character varying, 'sent'::character varying, 'delivered'::character varying, 'opened'::character varying, 'clicked'::character varying, 'responded'::character varying, 'bounced'::character varying, 'failed'::character varying, 'cancelled'::character varying]::text[]))");

            // Remove unsubscribed_at timestamp
            Schema::table('campaign_contacts', function (Blueprint $table) {
                $table->dropColumn('unsubscribed_at');
            });
        }
    }
};
