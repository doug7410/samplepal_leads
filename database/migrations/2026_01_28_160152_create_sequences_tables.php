<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sequences', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['draft', 'active', 'paused'])->default('draft');
            $table->json('entry_filter')->nullable();
            $table->timestamps();
        });

        Schema::create('sequence_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sequence_id')->constrained()->onDelete('cascade');
            $table->integer('step_order');
            $table->string('name');
            $table->string('subject');
            $table->text('content');
            $table->integer('delay_days')->default(0);
            $table->time('send_time')->nullable();
            $table->timestamps();

            $table->index(['sequence_id', 'step_order']);
        });

        Schema::create('sequence_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sequence_id')->constrained()->onDelete('cascade');
            $table->foreignId('contact_id')->constrained()->onDelete('cascade');
            $table->integer('current_step')->default(0);
            $table->enum('status', ['active', 'completed', 'exited'])->default('active');
            $table->timestamp('next_send_at')->nullable();
            $table->timestamp('entered_at')->nullable();
            $table->timestamp('exited_at')->nullable();
            $table->string('exit_reason')->nullable();
            $table->timestamps();

            $table->unique(['sequence_id', 'contact_id']);
            $table->index(['status', 'next_send_at']);
        });

        Schema::create('sequence_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sequence_contact_id')->constrained()->onDelete('cascade');
            $table->foreignId('sequence_step_id')->constrained('sequence_steps')->onDelete('cascade');
            $table->enum('status', ['pending', 'sent', 'delivered', 'opened', 'clicked', 'bounced', 'failed'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->string('message_id')->nullable();
            $table->timestamps();

            $table->index('message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sequence_emails');
        Schema::dropIfExists('sequence_contacts');
        Schema::dropIfExists('sequence_steps');
        Schema::dropIfExists('sequences');
    }
};
