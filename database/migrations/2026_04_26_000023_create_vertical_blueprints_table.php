<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vertical_blueprints', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name', 191);
            $table->string('business_model', 32)->default('service');
            $table->string('engine', 32)->default('servio');
            $table->text('description')->nullable();
            $table->json('default_offer_json')->nullable();
            $table->json('default_pricing_json')->nullable();
            $table->json('default_pages_json')->nullable();
            $table->json('default_tasks_json')->nullable();
            $table->json('default_channels_json')->nullable();
            $table->json('default_cta_json')->nullable();
            $table->json('default_image_queries_json')->nullable();
            $table->string('status', 32)->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vertical_blueprints');
    }
};
