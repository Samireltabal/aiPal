<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('telegram_conversation_id')->nullable()->after('telegram_chat_id');
            $table->string('whatsapp_conversation_id')->nullable()->after('whatsapp_phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['telegram_conversation_id', 'whatsapp_conversation_id']);
        });
    }
};
