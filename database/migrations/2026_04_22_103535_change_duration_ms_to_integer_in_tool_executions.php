<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tool_executions', function (Blueprint $table) {
            $table->unsignedInteger('duration_ms')->change();
        });
    }

    public function down(): void
    {
        Schema::table('tool_executions', function (Blueprint $table) {
            $table->unsignedSmallInteger('duration_ms')->change();
        });
    }
};
