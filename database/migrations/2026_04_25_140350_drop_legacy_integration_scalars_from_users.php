<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GitHub/GitLab/Jira credentials now live in the polymorphic `connections`
 * table (one row per account, encrypted JSON credentials). The legacy single-
 * account scalar columns are unused and are dropped here.
 *
 * The backfill migration (2026_04_24_153058_backfill_contexts_and_connections)
 * has already moved any pre-existing values into `connections` rows. This
 * migration must run AFTER the backfill in date order.
 *
 * Channel scalars (telegram/whatsapp/inbound_email) intentionally stay on
 * the users table for now — they're still 1:1 per user and webhook lookups
 * use them directly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'github_token',
                'gitlab_host',
                'gitlab_token',
                'jira_host',
                'jira_email',
                'jira_token',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('jira_host')->nullable();
            $table->string('jira_email')->nullable();
            $table->string('jira_token')->nullable();
            $table->string('gitlab_host')->default('https://gitlab.com');
            $table->string('gitlab_token')->nullable();
            $table->string('github_token')->nullable();
        });
    }
};
