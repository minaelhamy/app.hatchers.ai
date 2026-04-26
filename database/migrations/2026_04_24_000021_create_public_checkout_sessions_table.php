<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_checkout_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('founder_id')->constrained('founders')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('website_path', 191);
            $table->string('platform', 24);
            $table->string('category', 24);
            $table->string('offer_title', 191);
            $table->string('stripe_session_id', 191)->unique();
            $table->string('stripe_payment_intent_id', 191)->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 8)->default('USD');
            $table->string('payment_method_choice', 24)->default('online');
            $table->string('checkout_status', 24)->default('pending');
            $table->json('payload_json')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['website_path', 'checkout_status']);
            $table->index(['founder_id', 'platform', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_checkout_sessions');
    }
};
