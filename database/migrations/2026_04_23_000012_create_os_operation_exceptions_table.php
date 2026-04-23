<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('os_operation_exceptions', function (Blueprint $table) {
            $table->id();
            $table->string('module', 64);
            $table->string('operation', 120);
            $table->foreignId('founder_id')->nullable()->constrained('founders')->nullOnDelete();
            $table->text('message');
            $table->string('status', 32)->default('open');
            $table->json('payload_json')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['module', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('os_operation_exceptions');
    }
};
