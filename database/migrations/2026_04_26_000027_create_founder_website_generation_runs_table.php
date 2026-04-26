<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('founder_website_generation_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('founder_id')->constrained('founders')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('vertical_blueprint_id')->nullable()->constrained('vertical_blueprints')->nullOnDelete();
            $table->string('engine', 40);
            $table->string('status', 40)->default('queued');
            $table->json('input_json')->nullable();
            $table->json('output_json')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index(['founder_id', 'created_at']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('founder_website_generation_runs');
    }
};
