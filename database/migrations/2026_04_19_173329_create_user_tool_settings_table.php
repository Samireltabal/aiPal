<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_tool_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('tool', 60);
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'tool']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_tool_settings');
    }
};
