<?php

declare(strict_types=1);

namespace App\Modules\People\Http\Resources;

use App\Models\Interaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Interaction
 */
class InteractionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'person_id' => $this->person_id,
            'channel' => $this->channel,
            'direction' => $this->direction,
            'occurred_at' => $this->occurred_at?->toIso8601String(),
            'subject' => $this->subject,
            'summary' => $this->summary,
            'metadata' => $this->metadata ?? [],
        ];
    }
}
