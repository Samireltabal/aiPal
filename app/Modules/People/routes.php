<?php

declare(strict_types=1);

use App\Modules\People\Http\Controllers\InteractionController;
use App\Modules\People\Http\Controllers\PersonController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')
    ->middleware(['api', 'auth:sanctum'])
    ->group(function (): void {
        Route::get('people', [PersonController::class, 'index'])->name('api.v1.people.index');
        Route::post('people', [PersonController::class, 'store'])->name('api.v1.people.store');
        Route::get('people/{id}', [PersonController::class, 'show'])->name('api.v1.people.show');
        Route::patch('people/{id}', [PersonController::class, 'update'])->name('api.v1.people.update');
        Route::delete('people/{id}', [PersonController::class, 'destroy'])->name('api.v1.people.destroy');

        Route::get('people/{id}/interactions', [InteractionController::class, 'index'])
            ->name('api.v1.people.interactions.index');
        Route::post('people/{id}/interactions', [InteractionController::class, 'store'])
            ->name('api.v1.people.interactions.store');
    });
