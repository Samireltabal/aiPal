<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('briefing_enabled')->default(false)->after('is_admin');
            $table->time('briefing_time')->default('08:00')->after('briefing_enabled');
            $table->string('briefing_timezone')->default('UTC')->after('briefing_time');
            $table->timestamp('briefing_last_sent_at')->nullable()->after('briefing_timezone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['briefing_enabled', 'briefing_time', 'briefing_timezone', 'briefing_last_sent_at']);
        });
    }
};
