<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vertical_blueprints', function (Blueprint $table) {
            $table->json('funnel_framework_json')->nullable()->after('default_image_queries_json');
            $table->json('pricing_preset_json')->nullable()->after('funnel_framework_json');
            $table->json('channel_playbook_json')->nullable()->after('pricing_preset_json');
            $table->json('script_library_json')->nullable()->after('channel_playbook_json');
            $table->unsignedInteger('version_number')->default(1)->after('script_library_json');
        });

        Schema::create('vertical_blueprint_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vertical_blueprint_id')->constrained('vertical_blueprints')->cascadeOnDelete();
            $table->unsignedInteger('version_number')->default(1);
            $table->string('version_label', 64)->nullable();
            $table->text('change_summary')->nullable();
            $table->json('snapshot_json')->nullable();
            $table->foreignId('created_by_founder_id')->nullable()->constrained('founders')->nullOnDelete();
            $table->timestamps();

            $table->index(['vertical_blueprint_id', 'version_number']);
        });

        Schema::create('founder_pods', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191);
            $table->string('slug', 191)->unique();
            $table->foreignId('vertical_blueprint_id')->nullable()->constrained('vertical_blueprints')->nullOnDelete();
            $table->string('stage', 64)->nullable();
            $table->string('city', 191)->nullable();
            $table->string('status', 32)->default('active');
            $table->text('description')->nullable();
            $table->json('benchmark_json')->nullable();
            $table->timestamps();
        });

        Schema::create('founder_pod_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('founder_id')->constrained('founders')->cascadeOnDelete();
            $table->foreignId('founder_pod_id')->constrained('founder_pods')->cascadeOnDelete();
            $table->string('role', 32)->default('member');
            $table->string('status', 32)->default('active');
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['founder_id', 'founder_pod_id']);
        });

        Schema::create('founder_pod_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('founder_pod_id')->constrained('founder_pods')->cascadeOnDelete();
            $table->foreignId('founder_id')->constrained('founders')->cascadeOnDelete();
            $table->string('post_type', 32)->default('win');
            $table->string('title', 191);
            $table->text('body');
            $table->json('meta_json')->nullable();
            $table->timestamps();

            $table->index(['founder_pod_id', 'post_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('founder_pod_posts');
        Schema::dropIfExists('founder_pod_memberships');
        Schema::dropIfExists('founder_pods');
        Schema::dropIfExists('vertical_blueprint_versions');

        Schema::table('vertical_blueprints', function (Blueprint $table) {
            $table->dropColumn([
                'funnel_framework_json',
                'pricing_preset_json',
                'channel_playbook_json',
                'script_library_json',
                'version_number',
            ]);
        });
    }
};
