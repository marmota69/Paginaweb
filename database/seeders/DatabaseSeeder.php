<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->seedOwner();

        $this->call([
            SiteContentSeeder::class,
            PortfolioSeeder::class,
            AnalyticsSeeder::class,
        ]);
    }

    /**
     * Create the single account that administers the portfolio. Re-seeding
     * leaves an existing account — and its password — untouched.
     */
    private function seedOwner(): void
    {
        $email = config('portfolio.owner_email');

        if (User::query()->where('email', $email)->exists()) {
            $this->command->info("Owner account [{$email}] already exists — left untouched.");

            return;
        }

        User::factory()->create([
            'name' => 'Héctor Zamorano',
            'email' => $email,
        ]);

        $this->command->info("Owner account [{$email}] created with password [password].");
    }
}
