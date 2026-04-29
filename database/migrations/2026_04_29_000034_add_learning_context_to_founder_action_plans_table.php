<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('founder_action_plans', 'context')) {
            Schema::table('founder_action_plans', function (Blueprint $table): void {
                $table->string('context')->default('task')->after('platform');
            });
        }

        if (!Schema::hasColumn('founder_action_plans', 'available_on')) {
            Schema::table('founder_action_plans', function (Blueprint $table): void {
                $table->date('available_on')->nullable()->after('completed_at');
            });
        }

        if (!Schema::hasColumn('founder_action_plans', 'metadata_json')) {
            Schema::table('founder_action_plans', function (Blueprint $table): void {
                $table->json('metadata_json')->nullable()->after('available_on');
            });
        }

        if (!$this->indexExists('founder_action_plans', 'founder_action_plans_context_status_idx')) {
            Schema::table('founder_action_plans', function (Blueprint $table): void {
                $table->index(['founder_id', 'context', 'status'], 'founder_action_plans_context_status_idx');
            });
        }

        if (Schema::hasColumn('founder_action_plans', 'context')) {
            DB::table('founder_action_plans')
                ->whereNull('context')
                ->update(['context' => 'task']);
        }
    }

    public function down(): void
    {
        if ($this->indexExists('founder_action_plans', 'founder_action_plans_context_status_idx')) {
            Schema::table('founder_action_plans', function (Blueprint $table): void {
                $table->dropIndex('founder_action_plans_context_status_idx');
            });
        }

        $columnsToDrop = array_values(array_filter([
            Schema::hasColumn('founder_action_plans', 'context') ? 'context' : null,
            Schema::hasColumn('founder_action_plans', 'available_on') ? 'available_on' : null,
            Schema::hasColumn('founder_action_plans', 'metadata_json') ? 'metadata_json' : null,
        ]));

        if ($columnsToDrop !== []) {
            Schema::table('founder_action_plans', function (Blueprint $table) use ($columnsToDrop): void {
                $table->dropColumn($columnsToDrop);
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $database = DB::getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }
};
