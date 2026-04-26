<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('founder_launch_systems', function (Blueprint $table) {
            $table->id();
            $table->foreignId('founder_id')->constrained('founders')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('vertical_blueprint_id')->nullable()->constrained('vertical_blueprints')->nullOnDelete();
            $table->foreignId('founder_website_generation_run_id')->nullable()->constrained('founder_website_generation_runs')->nullOnDelete();
            $table->string('status', 32)->default('draft');
            $table->string('selected_engine', 32)->nullable();
            $table->json('launch_strategy_json')->nullable();
            $table->json('funnel_blocks_json')->nullable();
            $table->json('offer_stack_json')->nullable();
            $table->json('acquisition_system_json')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['founder_id', 'status']);
        });

        Schema::create('founder_lead_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('founder_id')->constrained('founders')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('vertical_blueprint_id')->nullable()->constrained('vertical_blueprints')->nullOnDelete();
            $table->string('channel_key', 64);
            $table->string('channel_label', 191);
            $table->string('status', 32)->default('recommended');
            $table->unsignedSmallInteger('priority_rank')->default(0);
            $table->unsignedInteger('daily_target')->default(0);
            $table->string('script_title', 191)->nullable();
            $table->text('script_body')->nullable();
            $table->text('offer_angle')->nullable();
            $table->json('meta_json')->nullable();
            $table->timestamp('adopted_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique(['founder_id', 'channel_key']);
            $table->index(['founder_id', 'status']);
        });

        Schema::create('founder_conversation_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('founder_id')->constrained('founders')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('founder_lead_id')->nullable()->constrained('founder_leads')->nullOnDelete();
            $table->foreignId('founder_lead_channel_id')->nullable()->constrained('founder_lead_channels')->nullOnDelete();
            $table->string('thread_key', 96);
            $table->string('source_channel', 64)->default('manual');
            $table->string('status', 32)->default('open');
            $table->json('recommended_sequence_json')->nullable();
            $table->text('latest_message')->nullable();
            $table->timestamp('next_follow_up_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->json('meta_json')->nullable();
            $table->timestamps();

            $table->unique(['founder_id', 'thread_key']);
            $table->index(['founder_id', 'status']);
            $table->index(['founder_id', 'next_follow_up_at']);
        });

        Schema::create('founder_pricing_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('founder_id')->constrained('founders')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('vertical_blueprint_id')->nullable()->constrained('vertical_blueprints')->nullOnDelete();
            $table->foreignId('founder_action_plan_id')->nullable()->constrained('founder_action_plans')->nullOnDelete();
            $table->string('recommendation_key', 96);
            $table->string('positioning', 64)->nullable();
            $table->string('title', 191);
            $table->text('description')->nullable();
            $table->string('currency', 16)->default('USD');
            $table->decimal('price', 10, 2)->default(0);
            $table->string('status', 32)->default('generated');
            $table->string('apply_target', 64)->nullable();
            $table->json('applied_payload_json')->nullable();
            $table->json('meta_json')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->unique(['founder_id', 'recommendation_key']);
            $table->index(['founder_id', 'status']);
        });

        Schema::create('founder_first_100_trackers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('founder_id')->constrained('founders')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('vertical_blueprint_id')->nullable()->constrained('vertical_blueprints')->nullOnDelete();
            $table->string('status', 32)->default('active');
            $table->unsignedInteger('target_customers')->default(100);
            $table->unsignedInteger('customers_won')->default(0);
            $table->unsignedInteger('active_leads')->default(0);
            $table->unsignedInteger('follow_up_due')->default(0);
            $table->string('best_channel', 64)->nullable();
            $table->unsignedSmallInteger('progress_percent')->default(0);
            $table->json('daily_plan_json')->nullable();
            $table->json('acquisition_summary_json')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['founder_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('founder_first_100_trackers');
        Schema::dropIfExists('founder_pricing_recommendations');
        Schema::dropIfExists('founder_conversation_threads');
        Schema::dropIfExists('founder_lead_channels');
        Schema::dropIfExists('founder_launch_systems');
    }
};
