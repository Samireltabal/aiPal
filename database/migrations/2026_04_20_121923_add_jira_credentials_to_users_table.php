<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('jira_host')->nullable()->after('whatsapp_conversation_id');
            $table->string('jira_email')->nullable()->after('jira_host');
            $table->string('jira_token')->nullable()->after('jira_email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['jira_host', 'jira_email', 'jira_token']);
        });
    }
};
