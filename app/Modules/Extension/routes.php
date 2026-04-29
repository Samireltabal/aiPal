<?php

declare(strict_types=1);

use App\Modules\Extension\Http\Controllers\ExtensionController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/extension')
    ->middleware(['api', 'auth:sanctum', 'ability:extension', 'throttle:extension'])
    ->group(function (): void {
        Route::get('ping', [ExtensionController::class, 'ping'])->name('api.v1.extension.ping');
        Route::get('contexts', [ExtensionController::class, 'contexts'])->name('api.v1.extension.contexts');
        Route::post('capture', [ExtensionController::class, 'capture'])->name('api.v1.extension.capture');
    });
