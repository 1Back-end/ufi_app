<?php

namespace Database\Seeders;

use App\Models\StatusFamiliale;
use Illuminate\Database\Seeder;

class StatusFamilialeSeeder extends Seeder
{
    public function run(): void
    {
        StatusFamiliale::factory()->create([
            'description_statusfam' => 'Marié',
        ]);

        StatusFamiliale::factory()->create([
            'description_statusfam' => 'Divorcé',
        ]);

        StatusFamiliale::factory()->create([
            'description_statusfam' => 'Veuf',
        ]);

        StatusFamiliale::factory()->create([
            'description_statusfam' => 'Veuve',
        ]);

        StatusFamiliale::factory()->create([
            'description_statusfam' => 'Celibataire',
        ]);
    }
}
