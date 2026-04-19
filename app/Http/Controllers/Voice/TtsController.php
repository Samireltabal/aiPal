<?php

declare(strict_types=1);

namespace App\Http\Controllers\Voice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Ai\Audio;

class TtsController extends Controller
{
    private const VALID_VOICES = ['alloy', 'ash', 'coral', 'echo', 'fable', 'onyx', 'nova', 'shimmer'];

    public function __invoke(Request $request): Response
    {
        $request->validate([
            'text' => 'required|string|max:4096',
            'voice' => 'nullable|string|in:alloy,ash,coral,echo,fable,onyx,nova,shimmer',
        ]);

        $voice = $request->input('voice', $request->user()->persona?->tts_voice ?? 'alloy');
        $text = $request->input('text');

        $audio = Audio::of($text)->voice($voice)->generate();

        return response($audio->content(), 200, [
            'Content-Type' => $audio->mimeType() ?? 'audio/mpeg',
            'Cache-Control' => 'no-cache',
            'Content-Disposition' => 'inline',
        ]);
    }
}
