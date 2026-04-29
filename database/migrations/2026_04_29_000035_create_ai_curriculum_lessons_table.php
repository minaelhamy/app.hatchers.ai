<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_curriculum_lessons', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('week_number');
            $table->unsignedTinyInteger('day_number');
            $table->unsignedSmallInteger('sequence')->unique();
            $table->string('source_book', 120);
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('summary');
            $table->longText('article_body');
            $table->text('action_prompt')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['week_number', 'day_number']);
            $table->index(['is_active', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_curriculum_lessons');
    }
};
