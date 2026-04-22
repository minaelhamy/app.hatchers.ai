<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('founder_weekly_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('founder_id')->constrained('founders')->cascadeOnDelete();
            $table->unsignedInteger('open_tasks')->default(0);
            $table->unsignedInteger('completed_tasks')->default(0);
            $table->unsignedInteger('open_milestones')->default(0);
            $table->unsignedInteger('completed_milestones')->default(0);
            $table->timestamp('next_meeting_at')->nullable();
            $table->string('weekly_focus')->nullable();
            $table->unsignedTinyInteger('weekly_progress_percent')->default(0);
            $table->timestamp('state_updated_at')->nullable();
            $table->timestamps();

            $table->unique('founder_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('founder_weekly_states');
    }
};
