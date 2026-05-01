<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('person_emails', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Stored lowercased; index lookups via citext-like behavior at the model layer.
            $table->string('email');
            $table->string('label')->nullable(); // work, personal, alias
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            // One person owns each email per user — second person can't claim the same address.
            $table->unique(['user_id', 'email']);
            $table->index(['email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_emails');
    }
};
