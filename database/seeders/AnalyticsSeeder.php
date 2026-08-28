<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Download;
use App\Models\Guide;
use App\Models\PageView;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AnalyticsSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed twelve months of visits and downloads so the admin dashboard has a
     * realistic shape to render before the site sees real traffic.
     */
    public function run(): void
    {
        $this->seedVisits();
        $this->seedDownloads();
    }

    /**
     * Spread a rising visit curve across the last twelve months.
     */
    private function seedVisits(): void
    {
        $monthly = [1240, 1385, 1520, 1465, 1710, 1890, 2140, 2075, 2310, 2480, 2655, 2820];
        $rows = [];

        foreach ($monthly as $offset => $total) {
            $month = now()->startOfMonth()->subMonths(11 - $offset);
            // One row per fifty visits keeps the seed fast while preserving the curve.
            $sampled = max(1, intdiv($total, 50));

            for ($i = 0; $i < $sampled; $i++) {
                $rows[] = [
                    'path' => '/',
                    'session_id' => Str::uuid()->toString(),
                    'created_at' => $month->copy()->addDays(random_int(0, $month->daysInMonth - 1))
                        ->addMinutes(random_int(0, 1439)),
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            PageView::query()->insert($chunk);
        }
    }

    /**
     * Attribute download events to the seeded courses and guides.
     */
    private function seedDownloads(): void
    {
        $weights = [
            'autenticacion-jwt-node' => 26,
            'react-pro' => 20,
            'ci-cd-github-actions' => 18,
            'js-fundamentos' => 15,
            'optimizacion-consultas-postgresql' => 9,
        ];

        $rows = [];

        foreach ($weights as $slug => $count) {
            $model = Course::query()->where('slug', $slug)->first()
                ?? Guide::query()->where('slug', $slug)->first();

            if (! $model) {
                continue;
            }

            for ($i = 0; $i < $count; $i++) {
                $rows[] = [
                    'downloadable_type' => $model::class,
                    'downloadable_id' => $model->getKey(),
                    'session_id' => Str::uuid()->toString(),
                    'created_at' => now()->subDays(random_int(0, 89))->subMinutes(random_int(0, 1439)),
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            Download::query()->insert($chunk);
        }
    }
}
