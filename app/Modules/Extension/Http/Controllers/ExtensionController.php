<?php

declare(strict_types=1);

namespace App\Modules\Extension\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Extension\Http\Requests\CaptureRequest;
use App\Modules\Extension\Services\CaptureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExtensionController extends Controller
{
    public function __construct(private readonly CaptureService $service) {}

    public function ping(Request $request): JsonResponse
    {
        $user = $request->user();
        $default = $user->contexts()->where('is_default', true)->first()
            ?? $user->contexts()->orderBy('id')->first();

        return response()->json([
            'ok' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'default_context' => $default ? [
                'id' => $default->id,
                'name' => $default->name,
                'kind' => $default->kind,
                'color' => $default->color,
            ] : null,
            'app_version' => config('app.version', '1.0'),
        ]);
    }

    public function contexts(Request $request): JsonResponse
    {
        $contexts = $request->user()
            ->contexts()
            ->whereNull('archived_at')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'kind', 'color', 'is_default']);

        return response()->json(['contexts' => $contexts]);
    }

    public function capture(CaptureRequest $request): JsonResponse
    {
        $record = $this->service->capture($request->user(), $request->validated());
        $kind = $request->validated('kind');

        return response()->json([
            'ok' => true,
            'kind' => $kind,
            'id' => $record->getKey(),
            'deep_link' => $this->deepLinkFor($kind),
        ], 201);
    }

    private function deepLinkFor(string $kind): string
    {
        return match ($kind) {
            'memory' => route('memories'),
            'task', 'reminder' => route('productivity'),
            'note' => route('productivity'),
            default => route('dashboard'),
        };
    }
}
