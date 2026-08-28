<?php

namespace Database\Factories;

use App\Models\Experience;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Experience>
 */
class ExperienceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'position' => $this->faker->numberBetween(0, 20),
            'period_from' => (string) $this->faker->numberBetween(2010, 2024),
            'period_to' => (string) $this->faker->numberBetween(2025, 2026),
            'role' => ['es' => 'Ingeniero de software', 'en' => 'Software engineer'],
            'company' => $this->faker->company(),
            'description' => ['es' => $this->faker->sentence(14), 'en' => $this->faker->sentence(14)],
            'is_published' => true,
        ];
    }

    /**
     * Indicate that this is the role currently held.
     */
    public function current(): static
    {
        return $this->state(fn (): array => ['period_to' => null]);
    }

    /**
     * Indicate that the role is hidden from the public site.
     */
    public function unpublished(): static
    {
        return $this->state(fn (): array => ['is_published' => false]);
    }
}
