<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('founder_icp_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('founder_id')->constrained('founders')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('primary_icp_name')->nullable();
            $table->string('age_range', 64)->nullable();
            $table->string('gender_focus', 64)->nullable();
            $table->string('life_stage', 128)->nullable();
            $table->json('pain_points_json')->nullable();
            $table->json('desired_outcomes_json')->nullable();
            $table->json('buying_triggers_json')->nullable();
            $table->json('objections_json')->nullable();
            $table->string('price_sensitivity', 128)->nullable();
            $table->json('primary_channels_json')->nullable();
            $table->json('local_area_focus_json')->nullable();
            $table->string('language_style', 191)->nullable();
            $table->timestamps();

            $table->index(['founder_id', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('founder_icp_profiles');
    }
};
