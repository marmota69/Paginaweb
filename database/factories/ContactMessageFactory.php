<?php

namespace Database\Factories;

use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactMessage>
 */
class ContactMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'subject' => rtrim($this->faker->sentence(4), '.'),
            'message' => $this->faker->paragraph(),
            'locale' => 'es',
            'ip_address' => $this->faker->ipv4(),
            'read_at' => null,
        ];
    }

    /**
     * Indicate that the admin has already read the message.
     */
    public function read(): static
    {
        return $this->state(fn (): array => ['read_at' => now()]);
    }
}
