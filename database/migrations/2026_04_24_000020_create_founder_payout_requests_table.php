<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('founder_payout_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('founder_id')->constrained('founders')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 8)->default('USD');
            $table->string('status', 24)->default('pending');
            $table->string('destination_summary', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->json('meta_json')->nullable();
            $table->timestamps();

            $table->index(['founder_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('founder_payout_requests');
    }
};
