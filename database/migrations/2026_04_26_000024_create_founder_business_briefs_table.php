<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('founder_business_briefs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('founder_id')->constrained('founders')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('vertical_blueprint_id')->nullable()->constrained('vertical_blueprints')->nullOnDelete();
            $table->string('business_name')->nullable();
            $table->longText('business_summary')->nullable();
            $table->text('problem_solved')->nullable();
            $table->text('core_offer')->nullable();
            $table->string('business_type_detail')->nullable();
            $table->string('location_city', 191)->nullable();
            $table->string('location_country', 64)->nullable();
            $table->string('service_radius', 128)->nullable();
            $table->string('delivery_scope', 191)->nullable();
            $table->text('proof_points')->nullable();
            $table->text('founder_story')->nullable();
            $table->json('constraints_json')->nullable();
            $table->string('status', 32)->default('draft');
            $table->timestamps();

            $table->index(['founder_id', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('founder_business_briefs');
    }
};
