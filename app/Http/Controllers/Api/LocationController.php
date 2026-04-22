<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Location\LocationUpdater;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function __construct(
        private readonly LocationUpdater $updater,
    ) {}

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'source' => 'sometimes|string|in:browser,manual',
        ]);

        $source = $data['source'] ?? 'browser';

        $result = $this->updater->updateFromCoordinates(
            $request->user(),
            (float) $data['latitude'],
            (float) $data['longitude'],
            $source,
        );

        return response()->json([
            'updated' => $result['updated'],
            'location' => $result['name'],
            'timezone' => $result['timezone'],
        ]);
    }

    public function clear(Request $request): JsonResponse
    {
        $this->updater->clear($request->user());

        return response()->json(['cleared' => true]);
    }
}
