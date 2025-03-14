<?php

namespace Database\Seeders;

use App\Models\Prefix;
use Illuminate\Database\Seeder;

class PrefixSeeder extends Seeder
{
    public function run(): void
    {
        Prefix::factory()->create([
            'prefixe' => "Epouse",
        ]);

        Prefix::factory()->create([
            'prefixe' => "Enfant",
        ]);

        Prefix::factory()->create([
            'prefixe' => "Bebe",
        ]);
    }
}
