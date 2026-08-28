<?php

namespace Database\Factories;

use App\Models\Guide;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Guide>
 */
class GuideFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = rtrim($this->faker->sentence(5), '.');

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.$this->faker->unique()->numberBetween(1, 100000),
            'category' => $this->faker->randomElement(['Backend', 'Frontend', 'DevOps', 'Bases de datos']),
            'tags' => implode(', ', $this->faker->randomElements(
                ['node', 'react', 'sql', 'docker', 'seguridad', 'jwt', 'rendimiento'],
                3,
            )),
            'content' => $this->faker->paragraph()."\n\n## ".rtrim($this->faker->sentence(3), '.')."\n".$this->faker->paragraph(),
            'image_path' => null,
            'published_on' => $this->faker->dateTimeBetween('-2 years')->format('Y-m-d'),
            'is_published' => true,
        ];
    }

    /**
     * Indicate that the guide is hidden from the public site.
     */
    public function unpublished(): static
    {
        return $this->state(fn (): array => ['is_published' => false]);
    }
}
