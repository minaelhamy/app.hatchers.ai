<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_intelligence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->text('target_audience')->nullable();
            $table->text('ideal_customer_profile')->nullable();
            $table->text('brand_voice')->nullable();
            $table->text('differentiators')->nullable();
            $table->text('content_goals')->nullable();
            $table->text('visual_style')->nullable();
            $table->text('core_offer')->nullable();
            $table->text('pricing_notes')->nullable();
            $table->text('primary_growth_goal')->nullable();
            $table->text('known_blockers')->nullable();
            $table->longText('last_summary')->nullable();
            $table->timestamp('intelligence_updated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_intelligence');
    }
};
