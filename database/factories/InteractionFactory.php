<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Interaction;
use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Interaction>
 */
class InteractionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'person_id' => Person::factory(),
            'user_id' => fn (array $attrs) => Person::find($attrs['person_id'])?->user_id,
            'channel' => Interaction::CHANNEL_EMAIL,
            'direction' => Interaction::DIRECTION_INBOUND,
            'occurred_at' => now()->subMinutes(fake()->numberBetween(1, 60 * 24 * 30)),
            'subject' => fake()->sentence(6),
            'summary' => fake()->sentence(),
            'metadata' => [],
        ];
    }
}
