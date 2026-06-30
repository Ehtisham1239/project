<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('trigger_event', [
                'message_received', 'lead_created', 'lead_stage_changed', 'no_reply_timeout', 'scheduled',
            ]);
            $table->json('trigger_conditions')->nullable(); // e.g. {"stage": "New"}
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('workflow_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->cascadeOnDelete();
            $table->integer('order')->default(0);
            $table->enum('action_type', [
                'send_message', 'send_template', 'assign_agent', 'change_lead_stage', 'create_lead', 'notify_internal', 'wait',
            ]);
            $table->json('action_params')->nullable(); // e.g. {"template_id": 3} or {"wait_minutes": 60}
            $table->timestamps();
        });

        Schema::create('knowledge_base_articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');           // source text the AI chatbot retrieves from
            $table->string('category')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->timestamp('scheduled_at');
            $table->integer('duration_minutes')->default(30);
            $table->enum('status', ['scheduled', 'confirmed', 'completed', 'cancelled', 'no_show'])->default('scheduled');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_chat_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->text('prompt');
            $table->text('response')->nullable();
            $table->boolean('was_auto_sent')->default(false); // false = suggested reply only
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_chat_logs');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('knowledge_base_articles');
        Schema::dropIfExists('workflow_actions');
        Schema::dropIfExists('workflows');
    }
};
