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
        Schema::create('email_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->longText('message');
            $table->boolean('send_to_all')->default(false)->index(); // Phase 1: send to all users
            $table->json('target_filters')->nullable(); // Phase 2+: store filters as JSON
            $table->enum('status', ['draft', 'pending', 'sending', 'completed', 'partial', 'failed'])->default('draft');
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->decimal('progress', 5, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('email_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_campaign_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->timestamps();

            // Add comprehensive indexes for better performance on email campaigns
            $table->index(['email_campaign_id', 'status'], 'email_campaign_recipients_campaign_status_idx');
            $table->index(['email_campaign_id', 'email'], 'email_campaign_recipients_campaign_email_idx');
            $table->index('status', 'email_campaign_recipients_status_idx');
            $table->index('updated_at', 'idx_email_campaign_recipients_updated_at');
            $table->unique(['email_campaign_id', 'email'], 'uniq_campaign_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_campaign_recipients');
        Schema::dropIfExists('email_campaigns');
    }
};
