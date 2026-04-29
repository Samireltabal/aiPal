<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Person;
use App\Models\PersonPhone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PersonPhone>
 */
class PersonPhoneFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'person_id' => Person::factory(),
            'user_id' => fn (array $attrs) => Person::find($attrs['person_id'])?->user_id,
            'phone' => '+1'.fake()->unique()->numerify('##########'),
            'is_primary' => true,
        ];
    }
}
