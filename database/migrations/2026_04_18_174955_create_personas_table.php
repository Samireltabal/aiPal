<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('assistant_name')->default('Pal');
            $table->string('tone')->default('friendly');
            $table->string('formality')->default('casual');
            $table->string('humor_level')->default('moderate');
            $table->text('backstory')->nullable();
            $table->text('system_prompt');
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personas');
    }
};
