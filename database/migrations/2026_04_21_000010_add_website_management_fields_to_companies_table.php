<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('website_engine')->nullable()->after('website_status');
            $table->string('custom_domain')->nullable()->after('website_url');
            $table->string('custom_domain_status')->default('not_connected')->after('custom_domain');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'website_engine',
                'custom_domain',
                'custom_domain_status',
            ]);
        });
    }
};
