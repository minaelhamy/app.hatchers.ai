<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('founder_wallet_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('founder_id')->constrained('founders')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('source_platform', 32)->nullable();
            $table->string('source_category', 32)->nullable();
            $table->string('source_reference', 120)->nullable();
            $table->string('entry_type', 24);
            $table->decimal('amount', 12, 2);
            $table->string('currency', 8)->default('USD');
            $table->string('status', 24)->default('available');
            $table->timestamp('available_at')->nullable();
            $table->json('meta_json')->nullable();
            $table->timestamps();

            $table->index(['founder_id', 'status', 'created_at']);
            $table->index(['source_platform', 'source_category', 'source_reference'], 'fwl_source_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('founder_wallet_ledgers');
    }
};
