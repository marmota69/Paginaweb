<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Download;
use App\Models\Guide;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Download>
 */
class DownloadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'downloadable_type' => Course::class,
            'downloadable_id' => Course::factory(),
            'session_id' => $this->faker->uuid(),
            'created_at' => $this->faker->dateTimeBetween('-60 days'),
        ];
    }

    /**
     * Attribute the download to the given course or guide.
     *
     * @param  Course|Guide  $model
     */
    public function ofContent(Model $model): static
    {
        return $this->state(fn (): array => [
            'downloadable_type' => $model::class,
            'downloadable_id' => $model->getKey(),
        ]);
    }
}
