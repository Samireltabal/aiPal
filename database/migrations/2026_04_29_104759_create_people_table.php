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

        if ($isPostgres) {
            // pg_trgm enables fuzzy ILIKE / similarity search over names + companies.
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        }

        Schema::create('people', function (Blueprint $table) use ($isPostgres): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('context_id')->nullable()->constrained()->nullOnDelete();

            $table->string('display_name');
            $table->string('given_name')->nullable();
            $table->string('family_name')->nullable();
            $table->string('nickname')->nullable();
            $table->string('company')->nullable();
            $table->string('title')->nullable();

            $table->text('notes')->nullable();

            if ($isPostgres) {
                $table->jsonb('tags')->default(DB::raw("'[]'::jsonb"));
                $table->jsonb('custom')->default(DB::raw("'{}'::jsonb"));
            } else {
                $table->json('tags')->nullable();
                $table->json('custom')->nullable();
            }

            $table->date('birthday')->nullable();
            $table->string('photo_url')->nullable();

            if ($isPostgres) {
                $table->vector('embedding', dimensions: 1536)->nullable();
            } else {
                $table->text('embedding')->nullable();
            }

            $table->timestamp('last_contact_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'last_contact_at']);
            $table->index(['user_id', 'context_id']);
        });

        if ($isPostgres) {
            DB::statement(
                'CREATE INDEX people_display_name_trgm_idx ON people USING gin (display_name gin_trgm_ops)'
            );
            DB::statement(
                'CREATE INDEX people_company_trgm_idx ON people USING gin (company gin_trgm_ops) WHERE company IS NOT NULL'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
