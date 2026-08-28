<?php

namespace Database\Factories;

use App\CourseLevel;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = rtrim($this->faker->sentence(4), '.');

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.$this->faker->unique()->numberBetween(1, 100000),
            'description' => $this->faker->paragraph(),
            'level' => $this->faker->randomElement(CourseLevel::cases()),
            'duration' => $this->faker->numberBetween(4, 30).' horas',
            'link' => $this->faker->url(),
            'image_path' => null,
            'position' => $this->faker->numberBetween(0, 20),
            'is_published' => true,
        ];
    }

    /**
     * Indicate that the course is hidden from the public site.
     */
    public function unpublished(): static
    {
        return $this->state(fn (): array => ['is_published' => false]);
    }
}
