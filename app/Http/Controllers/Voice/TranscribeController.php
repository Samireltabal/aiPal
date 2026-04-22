<?php

declare(strict_types=1);

namespace App\Http\Controllers\Voice;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Ai\Transcription;

class TranscribeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'audio' => 'required|file|mimetypes:audio/webm,audio/mp4,audio/mpeg,audio/mp3,audio/m4a,audio/wav,audio/ogg,audio/x-m4a,video/webm,video/mp4,application/octet-stream|max:25600',
        ]);

        $file = $request->file('audio');

        $pending = Transcription::fromUpload($file);
        $model = config('ai.stt_model') ?: null;
        $transcript = $model !== null ? $pending->generate(model: $model) : $pending->generate();

        return response()->json(['transcript' => (string) $transcript]);
    }
}
