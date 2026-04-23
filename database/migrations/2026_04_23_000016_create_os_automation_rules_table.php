<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('os_automation_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('founder_id')->constrained('founders')->cascadeOnDelete();
            $table->string('name');
            $table->string('trigger_type', 100);
            $table->string('module_scope', 50)->default('os');
            $table->text('condition_summary');
            $table->text('action_summary');
            $table->string('status', 30)->default('active');
            $table->json('metadata_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('os_automation_rules');
    }
};
