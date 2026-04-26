<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('founder_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('founder_id')->constrained('founders')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('lead_name', 191);
            $table->string('lead_channel', 64)->default('manual');
            $table->string('lead_stage', 32)->default('identified');
            $table->string('contact_handle', 191)->nullable();
            $table->string('city', 191)->nullable();
            $table->string('offer_name', 191)->nullable();
            $table->decimal('estimated_value', 10, 2)->nullable();
            $table->text('source_notes')->nullable();
            $table->text('stage_notes')->nullable();
            $table->timestamp('first_contacted_at')->nullable();
            $table->timestamp('last_followed_up_at')->nullable();
            $table->timestamp('next_follow_up_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamp('lost_at')->nullable();
            $table->json('meta_json')->nullable();
            $table->timestamps();

            $table->index(['founder_id', 'lead_stage']);
            $table->index(['founder_id', 'lead_channel']);
            $table->index(['founder_id', 'next_follow_up_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('founder_leads');
    }
};
