<?php

use App\Http\Controllers\HealthController;
use App\Livewire\Admin\Invitations;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Chat;
use App\Livewire\Onboarding;
use App\Livewire\Settings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/healthz', HealthController::class)->name('health');

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

    Route::middleware('persona')->group(function (): void {
        Route::get('/', Chat::class)->name('chat');
        Route::get('/settings', Settings::class)->name('settings');
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
