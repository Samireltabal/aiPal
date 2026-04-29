<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $isPostgres = DB::getDriverName() === 'pgsql';

        Schema::create('interactions', function (Blueprint $table) use ($isPostgres): void {
            $table->id();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('context_id')->nullable()->constrained()->nullOnDelete();

            // email | whatsapp | telegram | meeting | note | chat
            $table->string('channel');
            // inbound | outbound | none
            $table->string('direction')->default('none');

            $table->timestamp('occurred_at');
            $table->string('subject')->nullable();
            $table->text('summary')->nullable();
            $table->text('raw_excerpt')->nullable(); // first ~500 chars of source body

            if ($isPostgres) {
                $table->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            } else {
                $table->json('metadata')->nullable();
            }

            // Used for dedup so the same email doesn't get logged twice.
            $table->string('external_id')->nullable();

            if ($isPostgres) {
                $table->vector('embedding', dimensions: 1536)->nullable();
            } else {
                $table->text('embedding')->nullable();
            }

            $table->timestamps();

            $table->index(['user_id', 'person_id', 'occurred_at']);
            $table->index(['user_id', 'occurred_at']);
            $table->unique(['user_id', 'channel', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interactions');
    }
};
