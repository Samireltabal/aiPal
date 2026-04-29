<?php

declare(strict_types=1);

namespace App\Modules\People\Http\Resources;

use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Person
 */
class PersonResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'display_name' => $this->display_name,
            'given_name' => $this->given_name,
            'family_name' => $this->family_name,
            'nickname' => $this->nickname,
            'company' => $this->company,
            'title' => $this->title,
            'notes' => $this->notes,
            'tags' => $this->tags ?? [],
            'custom' => $this->custom ?? [],
            'birthday' => $this->birthday?->toDateString(),
            'photo_url' => $this->photo_url,
            'last_contact_at' => $this->last_contact_at?->toIso8601String(),
            'context_id' => $this->context_id,
            'emails' => $this->whenLoaded('emails', fn () => $this->emails->map(fn ($e) => [
                'id' => $e->id,
                'email' => $e->email,
                'label' => $e->label,
                'is_primary' => $e->is_primary,
            ])),
            'phones' => $this->whenLoaded('phones', fn () => $this->phones->map(fn ($p) => [
                'id' => $p->id,
                'phone' => $p->phone,
                'label' => $p->label,
                'is_primary' => $p->is_primary,
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
