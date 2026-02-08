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
        Schema::create('campaign_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->integer('position');
            $table->string('subject')->nullable();
            $table->text('content')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'position']);
        });

        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE campaign_segments ADD CONSTRAINT campaign_segments_status_check CHECK (status::text = ANY (ARRAY['draft'::character varying, 'in_progress'::character varying, 'completed'::character varying, 'failed'::character varying]::text[]))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_segments');
    }
};
