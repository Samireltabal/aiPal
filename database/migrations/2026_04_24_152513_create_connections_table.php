<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('context_id')->constrained('contexts')->cascadeOnDelete();
            $table->string('provider', 32); // 'google' | 'microsoft' | 'inbound_email' | 'telegram' | 'whatsapp' | 'jira' | 'gitlab' | 'github'
            $table->json('capabilities'); // ["mail", "calendar"] etc.
            $table->string('label')->nullable(); // "samir@acme.com"
            $table->string('identifier', 191)->nullable(); // provider primary ID
            $table->text('credentials')->nullable(); // encrypted JSON via model cast
            $table->boolean('is_default')->default(false);
            $table->boolean('enabled')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'provider']);
            $table->index(['context_id', 'provider']);
            $table->index(['user_id', 'provider', 'identifier']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connections');
    }
};
