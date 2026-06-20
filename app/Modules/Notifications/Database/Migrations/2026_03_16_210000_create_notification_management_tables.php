<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('category');
            $table->string('channel')->default('database');
            $table->string('format')->default('text');
            $table->string('subject_template')->nullable();
            $table->longText('body_template');
            $table->json('variables_schema')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['category', 'channel']);
        });

        Schema::create('notification_rules', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->string('trigger');
            $table->foreignId('template_id')->nullable()->constrained('notification_templates')->nullOnDelete();
            $table->string('channel')->default('database');
            $table->json('audience_config')->nullable();
            $table->boolean('approval_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['category', 'trigger']);
        });

        Schema::create('notification_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->string('trigger')->nullable();
            $table->string('title');
            $table->json('payload')->nullable();
            $table->string('format')->default('text');
            $table->string('status')->default('draft');
            $table->string('approval_status')->default('not_required');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['category', 'status']);
            $table->index(['approval_status', 'scheduled_at']);
        });

        Schema::create('notification_dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('notification_campaigns')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('channel')->default('database');
            $table->string('status')->default('pending');
            $table->string('provider_message_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'channel']);
            $table->index(['status', 'channel']);
        });

        Schema::create('notification_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('notification_campaigns')->cascadeOnDelete();
            $table->string('action');
            $table->text('note')->nullable();
            $table->foreignId('acted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_approvals');
        Schema::dropIfExists('notification_dispatches');
        Schema::dropIfExists('notification_campaigns');
        Schema::dropIfExists('notification_rules');
        Schema::dropIfExists('notification_templates');
    }
};
