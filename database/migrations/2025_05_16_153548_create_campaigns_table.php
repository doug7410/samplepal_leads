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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
