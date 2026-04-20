<?php

namespace Database\Factories;

use App\Models\Reminder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reminder>
 */
class ReminderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(3),
            'body' => $this->faker->optional()->sentence(),
            'remind_at' => now()->addHour(),
            'channel' => 'mail',
            'webhook_url' => null,
            'sent_at' => null,
        ];
    }
}
