<?php

use App\Modules\Chat\Http\Controllers\ChatController;
use App\Modules\Chat\Http\Controllers\ConversationController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware(['api', 'auth:sanctum'])->group(function (): void {
    Route::post('chat', ChatController::class)->name('api.v1.chat');

    Route::get('conversations', [ConversationController::class, 'index'])->name('api.v1.conversations.index');
    Route::get('conversations/search', [ConversationController::class, 'search'])->name('api.v1.conversations.search');
    Route::get('conversations/{id}/messages', [ConversationController::class, 'messages'])->name('api.v1.conversations.messages');
    Route::delete('conversations/{id}', [ConversationController::class, 'destroy'])->name('api.v1.conversations.destroy');
});
