<?php

declare(strict_types=1);

namespace App\Modules\Chat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChatRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:32768'],
            'conversation_id' => ['nullable', 'string', 'uuid'],
            'provider' => ['nullable', 'string', Rule::in(array_keys(config('ai.providers', [])))],
            'model' => ['nullable', 'string', 'max:100'],
        ];
    }
}
