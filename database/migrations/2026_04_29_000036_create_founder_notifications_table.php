<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('founder_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('founder_id')->constrained('founders')->cascadeOnDelete();
            $table->string('kind', 64)->default('general');
            $table->string('title');
            $table->string('meta', 255)->nullable();
            $table->string('app_key', 120)->nullable();
            $table->string('link_url', 500)->nullable();
            $table->boolean('is_read')->default(false);
            $table->json('data_json')->nullable();
            $table->timestamps();

            $table->index(['founder_id', 'created_at']);
            $table->index(['founder_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('founder_notifications');
    }
};
