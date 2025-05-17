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
            $campaigns = DB::table('campaigns')->get();
            
            // Backup existing constraints
            $foreignKeys = [];
            foreach (DB::select("PRAGMA foreign_key_list('campaigns')") as $fk) {
                $foreignKeys[] = $fk;
            }
            
            // Drop existing table
            Schema::dropIfExists('campaigns');
            
            // Recreate the table with the new enum values
            Schema::create('campaigns', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('subject');
                $table->text('content');
                $table->string('from_email');
                $table->string('from_name')->nullable();
                $table->string('reply_to')->nullable();
                $table->enum('status', ['draft', 'scheduled', 'in_progress', 'completed', 'paused', 'failed'])->default('draft');
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->json('filter_criteria')->nullable(); // For targeting specific contacts
                $table->timestamps();
            });
            
            // Restore the data
            foreach ($campaigns as $campaign) {
                // Convert any objects to arrays for insertion
                $data = (array) $campaign;
                unset($data['id']); // Let the database handle the auto-increment
                DB::table('campaigns')->insert($data);
            }
        } else {
            // For PostgreSQL, we need to modify the enum type or its constraint
            DB::statement('ALTER TABLE campaigns DROP CONSTRAINT IF EXISTS campaigns_status_check');
            DB::statement("ALTER TABLE campaigns ADD CONSTRAINT campaigns_status_check CHECK (status::text = ANY (ARRAY['draft'::character varying, 'scheduled'::character varying, 'in_progress'::character varying, 'completed'::character varying, 'paused'::character varying, 'failed'::character varying]::text[]))");
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
            $campaigns = DB::table('campaigns')->get();
            
            // First, convert any 'failed' status to 'draft' for backward compatibility
            foreach ($campaigns as $campaign) {
                if ($campaign->status === 'failed') {
                    DB::table('campaigns')
                        ->where('id', $campaign->id)
                        ->update(['status' => 'draft']);
                }
            }
            
            // Refresh the data
            $campaigns = DB::table('campaigns')->get();
            
            // Backup existing constraints
            $foreignKeys = [];
            foreach (DB::select("PRAGMA foreign_key_list('campaigns')") as $fk) {
                $foreignKeys[] = $fk;
            }
            
            // Drop existing table
            Schema::dropIfExists('campaigns');
            
            // Recreate the table with the original enum values
            Schema::create('campaigns', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('subject');
                $table->text('content');
                $table->string('from_email');
                $table->string('from_name')->nullable();
                $table->string('reply_to')->nullable();
                $table->enum('status', ['draft', 'scheduled', 'in_progress', 'completed', 'paused'])->default('draft');
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->json('filter_criteria')->nullable(); // For targeting specific contacts
                $table->timestamps();
            });
            
            // Restore the data
            foreach ($campaigns as $campaign) {
                // Convert any objects to arrays for insertion
                $data = (array) $campaign;
                unset($data['id']); // Let the database handle the auto-increment
                DB::table('campaigns')->insert($data);
            }
        } else {
            // For PostgreSQL, restore the original constraint
            DB::statement('ALTER TABLE campaigns DROP CONSTRAINT IF EXISTS campaigns_status_check');
            DB::statement("ALTER TABLE campaigns ADD CONSTRAINT campaigns_status_check CHECK (status::text = ANY (ARRAY['draft'::character varying, 'scheduled'::character varying, 'in_progress'::character varying, 'completed'::character varying, 'paused'::character varying]::text[]))");
        }
    }
};
