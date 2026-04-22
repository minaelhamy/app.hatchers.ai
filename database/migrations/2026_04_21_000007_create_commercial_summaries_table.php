<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commercial_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('founder_id')->constrained('founders')->cascadeOnDelete();
            $table->string('business_model')->default('hybrid');
            $table->unsignedInteger('product_count')->default(0);
            $table->unsignedInteger('service_count')->default(0);
            $table->unsignedInteger('order_count')->default(0);
            $table->unsignedInteger('booking_count')->default(0);
            $table->unsignedInteger('customer_count')->default(0);
            $table->decimal('gross_revenue', 12, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->timestamp('summary_updated_at')->nullable();
            $table->timestamps();

            $table->unique('founder_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_summaries');
    }
};
