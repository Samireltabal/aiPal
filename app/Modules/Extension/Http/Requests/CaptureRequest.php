<?php

declare(strict_types=1);

namespace App\Modules\Extension\Http\Requests;

use App\Models\Context;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CaptureRequest extends FormRequest
{
    public const KINDS = ['memory', 'task', 'note', 'reminder'];

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'kind' => ['required', 'string', Rule::in(self::KINDS)],
            'url' => ['required', 'url', 'max:2048'],
            'title' => ['required', 'string', 'max:512'],
            'prompt' => ['nullable', 'string', 'max:8192'],
            'selection' => ['nullable', 'string', 'max:50000'],
            'article' => ['nullable', 'string', 'max:100000'],
            'remind_at' => ['nullable', 'date'],
            'context_id' => [
                'nullable',
                'integer',
                Rule::exists((new Context)->getTable(), 'id')->where('user_id', $userId),
            ],
        ];
    }
}
