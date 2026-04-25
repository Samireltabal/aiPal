<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contexts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20); // 'work' | 'freelance' | 'personal'
            $table->string('name', 120);
            $table->string('slug', 60);
            $table->string('color', 9)->default('#6366f1'); // hex incl. alpha
            $table->boolean('is_default')->default(false);
            $table->json('inference_rules')->nullable(); // [{ type: 'sender_domain', value: '@acme.com', priority: 1 }, ...]
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'slug']);
            $table->index(['user_id', 'archived_at']);
        });

        // Seed every existing user with a default "Personal" context so Day 2's
        // backfill can attach connections without hitting FK errors.
        DB::table('users')->orderBy('id')->chunkById(100, function ($users): void {
            $now = now();
            $rows = [];

            foreach ($users as $user) {
                $rows[] = [
                    'user_id' => $user->id,
                    'kind' => 'personal',
                    'name' => 'Personal',
                    'slug' => 'personal',
                    'color' => '#6366f1',
                    'is_default' => true,
                    'inference_rules' => null,
                    'archived_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($rows !== []) {
                DB::table('contexts')->insert($rows);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contexts');
    }
};
