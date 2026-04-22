<?php

declare(strict_types=1);

use App\Http\Controllers\Api\LocationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('v1')->group(function (): void {
    Route::post('location', [LocationController::class, 'update'])->name('api.v1.location.update');
    Route::delete('location', [LocationController::class, 'clear'])->name('api.v1.location.clear');
});
