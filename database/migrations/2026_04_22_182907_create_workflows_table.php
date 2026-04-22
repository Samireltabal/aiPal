<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('enabled')->default(true);

            $table->text('prompt');
            $table->json('enabled_tool_names');
            $table->string('delivery_channel', 20)->default('notification');

            $table->string('trigger_type', 20);
            $table->string('cron_expression')->nullable();
            $table->string('webhook_token', 64)->nullable()->unique();
            $table->string('message_channel', 20)->nullable();
            $table->string('message_trigger_pattern')->nullable();

            $table->timestamp('last_run_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'enabled']);
            $table->index(['trigger_type', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflows');
    }
};
