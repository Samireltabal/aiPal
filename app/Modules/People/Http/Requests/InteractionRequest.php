<?php

declare(strict_types=1);

namespace App\Modules\People\Http\Requests;

use App\Models\Interaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InteractionRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'channel' => ['required', 'string', Rule::in(Interaction::CHANNELS)],
            'direction' => ['nullable', 'string', Rule::in(Interaction::DIRECTIONS)],
            'occurred_at' => ['nullable', 'date'],
            'subject' => ['nullable', 'string', 'max:512'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'raw_excerpt' => ['nullable', 'string', 'max:5000'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
