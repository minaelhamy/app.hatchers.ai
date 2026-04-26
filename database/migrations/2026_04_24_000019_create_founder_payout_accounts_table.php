<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('founder_payout_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('founder_id')->constrained('founders')->cascadeOnDelete();
            $table->string('account_holder_name', 191);
            $table->string('bank_name', 191);
            $table->string('account_number', 191)->nullable();
            $table->string('iban', 191)->nullable();
            $table->string('swift_code', 64)->nullable();
            $table->string('routing_number', 64)->nullable();
            $table->string('bank_country', 64)->nullable();
            $table->string('bank_currency', 8)->default('USD');
            $table->string('status', 24)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('founder_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('founder_payout_accounts');
    }
};
