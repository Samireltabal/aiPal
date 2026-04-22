<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default AI Provider Names
    |--------------------------------------------------------------------------
    */

    'default' => strtolower(env('AI_DEFAULT_PROVIDER', 'anthropic')),
    'default_for_images' => env('AI_DEFAULT_IMAGES_PROVIDER', 'openai'),
    'default_for_audio' => env('AI_DEFAULT_AUDIO_PROVIDER', env('TTS_PROVIDER', 'openai')),
    'default_for_transcription' => env('AI_DEFAULT_STT_PROVIDER', env('STT_PROVIDER', 'openai')),
    'default_for_embeddings' => env('AI_DEFAULT_EMBEDDINGS_PROVIDER', 'openai'),
    'default_for_reranking' => 'cohere',

    'embedding_model' => env('AI_EMBEDDING_MODEL') ?: null,
    'embedding_dimensions' => (int) env('AI_EMBEDDING_DIMENSIONS', 1536),

    'stt_model' => env('AI_STT_MODEL') ?: null,
    'tts_model' => env('AI_TTS_MODEL') ?: null,

    /*
    |--------------------------------------------------------------------------
    | Default Models Per Provider
    |--------------------------------------------------------------------------
    |
    | These are used by the AiTestCommand and anywhere a per-provider model
    | override is needed. The SDK picks up provider-level model defaults from
    | the provider config when a #[Model] attribute is not present.
    |
    */

    'models' => [
        'anthropic' => env('ANTHROPIC_DEFAULT_MODEL', 'claude-sonnet-4-6'),
        'openai' => env('OPENAI_DEFAULT_MODEL', 'gpt-4o'),
        'deepseek' => env('DEEPSEEK_DEFAULT_MODEL', 'deepseek-chat'),
        'xai' => env('XAI_DEFAULT_MODEL', 'grok-2-latest'),
        'gemini' => env('GEMINI_DEFAULT_MODEL', 'gemini-2.0-flash'),
        'ollama' => env('OLLAMA_DEFAULT_MODEL', 'llama3.2'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Agent Provider & Model Overrides
    |--------------------------------------------------------------------------
    |
    | Each background agent can use a different provider/model from the default.
    | Leave blank to fall back to the default provider and its default model.
    |
    */

    'agents' => [
        'memory_extractor' => [
            'provider' => env('MEMORY_EXTRACTOR_PROVIDER') ?: null,
            'model' => env('MEMORY_EXTRACTOR_MODEL') ?: null,
        ],
        'daily_briefing' => [
            'provider' => env('DAILY_BRIEFING_PROVIDER') ?: null,
            'model' => env('DAILY_BRIEFING_MODEL') ?: null,
        ],
        'reminder_parser' => [
            'provider' => env('REMINDER_PARSER_PROVIDER') ?: null,
            'model' => env('REMINDER_PARSER_MODEL') ?: null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    */

    'caching' => [
        'embeddings' => [
            'cache' => false,
            'store' => env('CACHE_STORE', 'redis'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Providers
    |--------------------------------------------------------------------------
    */

    'providers' => [
        'anthropic' => [
            'driver' => 'anthropic',
            'key' => env('ANTHROPIC_API_KEY'),
            'url' => env('ANTHROPIC_URL', 'https://api.anthropic.com/v1'),
        ],

        'deepseek' => [
            'driver' => 'deepseek',
            'key' => env('DEEPSEEK_API_KEY'),
            'url' => env('DEEPSEEK_URL', 'https://api.deepseek.com'),
        ],

        'eleven' => [
            'driver' => 'eleven',
            'key' => env('ELEVENLABS_API_KEY'),
        ],

        'gemini' => [
            'driver' => 'gemini',
            'key' => env('GEMINI_API_KEY'),
        ],

        'ollama' => [
            'driver' => 'ollama',
            'key' => env('OLLAMA_API_KEY', ''),
            'url' => env('OLLAMA_HOST', 'http://ollama:11434'),
        ],

        'openai' => [
            'driver' => 'openai',
            'key' => env('OPENAI_API_KEY'),
            'url' => env('OPENAI_URL', 'https://api.openai.com/v1'),
        ],

        'xai' => [
            'driver' => 'xai',
            'key' => env('XAI_API_KEY'),
            'url' => env('XAI_URL', 'https://api.x.ai/v1'),
        ],
    ],

];
