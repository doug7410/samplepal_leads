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
        // For SQLite (tests), need to recreate the table with the new enum values
        if (config('database.default') === 'sqlite') {
            // Get the existing table data
            $campaignContacts = DB::table('campaign_contacts')->get();
            
            // Backup existing constraints
            $foreignKeys = [];
            foreach (DB::select("PRAGMA foreign_key_list('campaign_contacts')") as $fk) {
                $foreignKeys[] = $fk;
            }
            
            // Drop existing table
            Schema::dropIfExists('campaign_contacts');
            
            // Recreate the table with the new enum values
            Schema::create('campaign_contacts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
                $table->foreignId('contact_id')->constrained()->onDelete('cascade');
                $table->enum('status', [
                    'pending', 'processing', 'sent', 'delivered',
                    'opened', 'clicked', 'responded', 'bounced', 
                    'failed', 'cancelled'
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
                
                // Ensure each contact is only added once per campaign
                $table->unique(['campaign_id', 'contact_id']);
            });
            
            // Restore the data
            foreach ($campaignContacts as $cc) {
                // Convert any objects to arrays for insertion
                $data = (array) $cc;
                unset($data['id']); // Let the database handle the auto-increment
                DB::table('campaign_contacts')->insert($data);
            }
        } else {
            // For PostgreSQL, modify the enum type
            DB::statement('ALTER TABLE campaign_contacts DROP CONSTRAINT IF EXISTS campaign_contacts_status_check');
            DB::statement("ALTER TABLE campaign_contacts ADD CONSTRAINT campaign_contacts_status_check CHECK (status::text = ANY (ARRAY['pending'::character varying, 'processing'::character varying, 'sent'::character varying, 'delivered'::character varying, 'opened'::character varying, 'clicked'::character varying, 'responded'::character varying, 'bounced'::character varying, 'failed'::character varying, 'cancelled'::character varying]::text[]))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // For SQLite (tests), need to recreate the table with the original enum values
        if (config('database.default') === 'sqlite') {
            // Get the existing table data
            $campaignContacts = DB::table('campaign_contacts')->get();
            
            // First, convert any 'cancelled' status to 'failed' for backward compatibility
            foreach ($campaignContacts as $cc) {
                if ($cc->status === 'cancelled') {
                    DB::table('campaign_contacts')
                        ->where('id', $cc->id)
                        ->update(['status' => 'failed']);
                }
            }
            
            // Refresh the data
            $campaignContacts = DB::table('campaign_contacts')->get();
            
            // Backup existing constraints
            $foreignKeys = [];
            foreach (DB::select("PRAGMA foreign_key_list('campaign_contacts')") as $fk) {
                $foreignKeys[] = $fk;
            }
            
            // Drop existing table
            Schema::dropIfExists('campaign_contacts');
            
            // Recreate the table with the original enum values
            Schema::create('campaign_contacts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
                $table->foreignId('contact_id')->constrained()->onDelete('cascade');
                $table->enum('status', [
                    'pending', 'processing', 'sent', 'delivered',
                    'opened', 'clicked', 'responded', 'bounced', 
                    'failed'
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
                
                // Ensure each contact is only added once per campaign
                $table->unique(['campaign_id', 'contact_id']);
            });
            
            // Restore the data
            foreach ($campaignContacts as $cc) {
                // Convert any objects to arrays for insertion
                $data = (array) $cc;
                unset($data['id']); // Let the database handle the auto-increment
                DB::table('campaign_contacts')->insert($data);
            }
        } else {
            // For PostgreSQL, restore the original constraint
            DB::statement('ALTER TABLE campaign_contacts DROP CONSTRAINT IF EXISTS campaign_contacts_status_check');
            DB::statement("ALTER TABLE campaign_contacts ADD CONSTRAINT campaign_contacts_status_check CHECK (status::text = ANY (ARRAY['pending'::character varying, 'processing'::character varying, 'sent'::character varying, 'delivered'::character varying, 'opened'::character varying, 'clicked'::character varying, 'responded'::character varying, 'bounced'::character varying, 'failed'::character varying]::text[]))");
        }
    }
};
