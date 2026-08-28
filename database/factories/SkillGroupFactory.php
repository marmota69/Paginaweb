<?php

namespace Database\Factories;

use App\Models\SkillGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SkillGroup>
 */
class SkillGroupFactory extends Factory
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
            'name' => ['es' => 'Backend', 'en' => 'Backend'],
        ];
    }
}
