<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Context;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Context>
 */
class ContextFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement(['Personal', 'Acme Corp', 'Project Phoenix', 'Side Hustle']);

        return [
            'user_id' => User::factory(),
            'kind' => Context::KIND_PERSONAL,
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(4),
            'color' => '#6366f1',
            'is_default' => false,
            'inference_rules' => null,
            'archived_at' => null,
        ];
    }

    public function work(string $name = 'Acme Corp'): static
    {
        return $this->state(fn () => [
            'kind' => Context::KIND_WORK,
            'name' => $name,
            'slug' => Str::slug($name),
        ]);
    }

    public function freelance(string $name = 'Project Phoenix'): static
    {
        return $this->state(fn () => [
            'kind' => Context::KIND_FREELANCE,
            'name' => $name,
            'slug' => Str::slug($name),
        ]);
    }

    public function personal(): static
    {
        return $this->state(fn () => [
            'kind' => Context::KIND_PERSONAL,
            'name' => 'Personal',
            'slug' => 'personal',
            'is_default' => true,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn () => ['archived_at' => now()]);
    }
}
