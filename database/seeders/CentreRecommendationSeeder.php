<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Centre;

class CentreRecommendationSeeder extends Seeder
{
    public function run(): void
    {
        Centre::where('reference', 'GTLABO')->update([
            'is_exam_recommendation_center' => true,
        ]);

        Centre::where('reference', 'CMGT')->update([
            'is_acte_recommendation_center' => true,
        ]);
    }
}
