<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Person;
use App\Models\PersonEmail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PersonEmail>
 */
class PersonEmailFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'person_id' => Person::factory(),
            // user_id pulled from the parent person via afterMaking when used with `for()`.
            // Allow callers to override; when used standalone, set the user explicitly.
            'user_id' => fn (array $attrs) => Person::find($attrs['person_id'])?->user_id,
            'email' => strtolower(fake()->unique()->safeEmail()),
            'is_primary' => true,
        ];
    }
}
