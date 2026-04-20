<?php

use App\Http\Controllers\Google\GoogleAuthController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\Telegram\TelegramWebhookController;
use App\Http\Controllers\Voice\TranscribeController;
use App\Http\Controllers\Voice\TtsController;
use App\Http\Controllers\WhatsApp\WhatsAppWebhookController;
use App\Livewire\Admin\Invitations;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Chat;
use App\Livewire\Documents;
use App\Livewire\Memories;
use App\Livewire\Onboarding;
use App\Livewire\Productivity;
use App\Livewire\Settings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/healthz', HealthController::class)->name('health');

// Telegram webhook — no auth, CSRF excluded in bootstrap/app.php
Route::post('/webhooks/telegram', TelegramWebhookController::class)->name('webhooks.telegram');

// WhatsApp webhook — GET for verification challenge, POST for messages, no auth, CSRF excluded
Route::get('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'verify'])->name('webhooks.whatsapp.verify');
Route::post('/webhooks/whatsapp', WhatsAppWebhookController::class)->name('webhooks.whatsapp');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
});

Route::post('/logout', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/onboarding', Onboarding::class)->name('onboarding');

    Route::get('/google/auth', [GoogleAuthController::class, 'redirect'])->name('google.auth');
    Route::get('/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');
    Route::delete('/google/disconnect', [GoogleAuthController::class, 'disconnect'])->name('google.disconnect');

    Route::middleware('persona')->group(function (): void {
        Route::get('/', Chat::class)->name('chat');
        Route::post('/voice/transcribe', TranscribeController::class)->name('voice.transcribe');
        Route::post('/voice/tts', TtsController::class)->name('voice.tts');
        Route::get('/settings', Settings::class)->name('settings');
        Route::get('/memories', Memories::class)->name('memories');
        Route::get('/documents', Documents::class)->name('documents');
        Route::get('/productivity', Productivity::class)->name('productivity');
        Route::get('/memories/export', function () {
            $user = Auth::user();
            $memories = $user->memories()->latest()->get(['content', 'source', 'created_at']);
            $data = [
                'memories' => $memories->map(fn ($m) => [
                    'content' => $m->content,
                    'source' => $m->source,
                    'remembered_at' => $m->created_at?->toIso8601String(),
                ])->all(),
                'exported_at' => now()->toIso8601String(),
            ];

            return response((string) json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 200, [
                'Content-Type' => 'application/json',
                'Content-Disposition' => 'attachment; filename="memories.json"',
            ]);
        })->name('memories.export');
        Route::get('/persona/export', function () {
            $persona = Auth::user()->persona;
            $data = [
                'assistant_name' => $persona->assistant_name,
                'tone' => $persona->tone,
                'formality' => $persona->formality,
                'humor_level' => $persona->humor_level,
                'backstory' => $persona->backstory,
                'system_prompt' => $persona->system_prompt,
                'exported_at' => now()->toIso8601String(),
            ];
            $filename = 'persona-'.str($persona->assistant_name)->slug().'.json';

            return response((string) json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 200, [
                'Content-Type' => 'application/json',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        })->name('persona.export');

        Route::middleware('admin')->group(function (): void {
            Route::get('/admin/invitations', Invitations::class)->name('admin.invitations');
        });
    });
});
