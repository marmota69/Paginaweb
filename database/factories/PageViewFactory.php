<?php

namespace Database\Factories;

use App\Models\PageView;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PageView>
 */
class PageViewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'path' => $this->faker->randomElement(['/', '/cursos/react-pro', '/guias/jwt-node']),
            'session_id' => $this->faker->uuid(),
            'created_at' => $this->faker->dateTimeBetween('-12 months'),
        ];
    }

    /**
     * Record the view at a specific moment in time.
     */
    public function at(\DateTimeInterface $moment): static
    {
        return $this->state(fn (): array => ['created_at' => $moment]);
    }
}
