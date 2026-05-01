<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('person_phones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // E.164 format expected; normalization happens at the resolver.
            $table->string('phone');
            $table->string('label')->nullable(); // mobile, work, home
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'phone']);
            $table->index(['phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_phones');
    }
};
