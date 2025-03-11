<?php

namespace Database\Seeders;

use App\Models\Sexe;
use App\Models\User;
use Database\Factories\SexeFactory;
use Illuminate\Database\Seeder;

class SexesSeeder extends Seeder
{
    public function run(): void
    {
       Sexe::factory()->create([
            'description_sex' => 'Masculin',
        ]);

        Sexe::factory()->create([
            'description_sex' => 'Feminin',
        ]);
    }
}
