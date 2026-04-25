<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<int, string> */
    private const TABLES = [
        'tasks',
        'reminders',
        'notes',
        'memories',
        'agent_conversations',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t): void {
                // Nullable at first — Day 2 backfill populates; future rows
                // always carry context_id set by the creating call site.
                $t->foreignId('context_id')
                    ->nullable()
                    ->constrained('contexts')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table): void {
                $t->dropForeign([$table.'_context_id_foreign']);
                $t->dropColumn('context_id');
            });
        }
    }
};
