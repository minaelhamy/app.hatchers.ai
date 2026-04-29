<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('founder_action_plans', function (Blueprint $table): void {
            $table->string('context')->default('task')->after('platform');
            $table->date('available_on')->nullable()->after('completed_at');
            $table->json('metadata_json')->nullable()->after('available_on');
            $table->index(['founder_id', 'context', 'status'], 'founder_action_plans_context_status_idx');
        });

        DB::table('founder_action_plans')->update(['context' => 'task']);
    }

    public function down(): void
    {
        Schema::table('founder_action_plans', function (Blueprint $table): void {
            $table->dropIndex('founder_action_plans_context_status_idx');
            $table->dropColumn(['context', 'available_on', 'metadata_json']);
        });
    }
};
