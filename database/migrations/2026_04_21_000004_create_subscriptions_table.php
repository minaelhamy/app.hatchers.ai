<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('founder_id')->constrained('founders')->cascadeOnDelete();
            $table->string('plan_code');
            $table->string('plan_name');
            $table->string('billing_status')->default('pending');
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('mentor_phase_started_at')->nullable();
            $table->timestamp('mentor_phase_ends_at')->nullable();
            $table->string('transitions_to_plan_code')->nullable();
            $table->timestamp('transitions_on')->nullable();
            $table->timestamp('next_billing_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['founder_id', 'billing_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
