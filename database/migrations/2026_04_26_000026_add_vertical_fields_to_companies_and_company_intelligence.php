<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('vertical_blueprint_id')->nullable()->after('business_model')->constrained('vertical_blueprints')->nullOnDelete();
            $table->string('primary_city')->nullable()->after('stage');
            $table->string('service_radius')->nullable()->after('primary_city');
            $table->string('primary_goal')->nullable()->after('service_radius');
            $table->string('launch_stage')->default('brief_pending')->after('primary_goal');
            $table->string('website_generation_status')->default('not_started')->after('launch_stage');
        });

        Schema::table('company_intelligence', function (Blueprint $table) {
            $table->text('primary_icp_name')->nullable()->after('ideal_customer_profile');
            $table->text('problem_solved')->nullable()->after('primary_icp_name');
            $table->text('objections')->nullable()->after('known_blockers');
            $table->text('buying_triggers')->nullable()->after('objections');
            $table->text('local_market_notes')->nullable()->after('buying_triggers');
        });
    }

    public function down(): void
    {
        Schema::table('company_intelligence', function (Blueprint $table) {
            $table->dropColumn([
                'primary_icp_name',
                'problem_solved',
                'objections',
                'buying_triggers',
                'local_market_notes',
            ]);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vertical_blueprint_id');
            $table->dropColumn([
                'primary_city',
                'service_radius',
                'primary_goal',
                'launch_stage',
                'website_generation_status',
            ]);
        });
    }
};
