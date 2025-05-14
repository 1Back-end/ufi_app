<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\UniteProduit;

class UniteProduitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Créer 10 unités de produits avec la factory
        UniteProduit::factory()->count(10)->create();
    }
}
