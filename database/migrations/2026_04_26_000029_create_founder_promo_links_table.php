<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('founder_promo_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('founder_id')->constrained('founders')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('title', 191);
            $table->string('source_channel', 64)->default('flyer');
            $table->string('promo_code', 64)->index();
            $table->string('destination_path', 191)->nullable();
            $table->string('cta_label', 191)->nullable();
            $table->string('offer_title', 191)->nullable();
            $table->string('status', 32)->default('active');
            $table->json('meta_json')->nullable();
            $table->timestamps();

            $table->index(['founder_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('founder_promo_links');
    }
};
