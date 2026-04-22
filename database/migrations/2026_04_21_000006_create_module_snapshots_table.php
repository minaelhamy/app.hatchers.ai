<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('founder_id')->constrained('founders')->cascadeOnDelete();
            $table->string('module');
            $table->string('snapshot_version')->default('v1');
            $table->unsignedTinyInteger('readiness_score')->default(0);
            $table->json('payload_json');
            $table->timestamp('snapshot_updated_at')->nullable();
            $table->timestamps();

            $table->unique(['founder_id', 'module']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_snapshots');
    }
};
