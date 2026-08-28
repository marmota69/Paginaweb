<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = rtrim($this->faker->sentence(2), '.');

        return [
            'position' => $this->faker->numberBetween(0, 20),
            'name' => $name,
            'year' => (string) $this->faker->numberBetween(2018, 2026),
            'type' => ['es' => 'Plataforma web', 'en' => 'Web platform'],
            'description' => ['es' => $this->faker->sentence(12), 'en' => $this->faker->sentence(12)],
            'stack' => $this->faker->randomElements(['Laravel', 'React', 'Vue', 'PostgreSQL', 'Redis', 'Docker'], 3),
            'url' => null,
            'is_published' => true,
        ];
    }

    /**
     * Indicate that the project is hidden from the public site.
     */
    public function unpublished(): static
    {
        return $this->state(fn (): array => ['is_published' => false]);
    }
}
