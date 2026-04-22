<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('founder_action_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('founder_id')->constrained('founders')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('platform')->default('os');
            $table->unsignedTinyInteger('priority')->default(50);
            $table->string('status')->default('pending');
            $table->string('cta_label')->nullable();
            $table->string('cta_url')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['founder_id', 'status', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('founder_action_plans');
    }
};
