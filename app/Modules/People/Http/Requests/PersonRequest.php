<?php

declare(strict_types=1);

namespace App\Modules\People\Http\Requests;

use App\Models\Context;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PersonRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'display_name' => ['required', 'string', 'max:255'],
            'given_name' => ['nullable', 'string', 'max:255'],
            'family_name' => ['nullable', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:60'],
            'custom' => ['nullable', 'array'],
            'birthday' => ['nullable', 'date'],
            'photo_url' => ['nullable', 'url', 'max:2048'],
            'context_id' => [
                'nullable', 'integer',
                Rule::exists((new Context)->getTable(), 'id')->where('user_id', $userId),
            ],
            'emails' => ['nullable', 'array'],
            'emails.*' => ['email', 'max:255'],
            'phones' => ['nullable', 'array'],
            'phones.*' => ['string', 'max:32'],
        ];
    }
}
