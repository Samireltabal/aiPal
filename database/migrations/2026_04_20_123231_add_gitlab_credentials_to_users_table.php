<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('gitlab_host')->default('https://gitlab.com')->after('default_reminder_channel');
            $table->string('gitlab_token')->nullable()->after('gitlab_host');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['gitlab_host', 'gitlab_token']);
        });
    }
};
