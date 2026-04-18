<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default AI Provider Names
    |--------------------------------------------------------------------------
    */

    'default' => env('AI_DEFAULT_PROVIDER', 'anthropic'),
    'default_for_images' => env('AI_DEFAULT_IMAGES_PROVIDER', 'openai'),
    'default_for_audio' => env('AI_DEFAULT_AUDIO_PROVIDER', 'openai'),
    'default_for_transcription' => env('AI_DEFAULT_STT_PROVIDER', 'openai'),
    'default_for_embeddings' => env('AI_DEFAULT_EMBEDDINGS_PROVIDER', 'openai'),
    'default_for_reranking' => 'cohere',

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
