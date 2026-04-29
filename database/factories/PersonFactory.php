<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Person;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Person>
 */
class PersonFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $first = fake()->firstName();
        $last = fake()->lastName();

        return [
            'user_id' => User::factory(),
            'context_id' => null,
            'display_name' => "$first $last",
            'given_name' => $first,
            'family_name' => $last,
            'company' => fake()->boolean(60) ? fake()->company() : null,
            'tags' => [],
            'custom' => [],
        ];
    }
}
