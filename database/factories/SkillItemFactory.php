<?php

namespace Database\Factories;

use App\Models\SkillGroup;
use App\Models\SkillItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SkillItem>
 */
class SkillItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'skill_group_id' => SkillGroup::factory(),
            'position' => $this->faker->numberBetween(0, 20),
            'name' => $this->faker->randomElement(['PHP', 'JavaScript', 'SQL', 'Docker', 'Redis']),
            'percent' => $this->faker->numberBetween(40, 100),
        ];
    }
}
