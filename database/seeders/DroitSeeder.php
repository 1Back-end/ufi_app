<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Droit;

class DroitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $droits = [
            ['nom_droit' => 'Créer', 'status_droit' => 'actif'],
            ['nom_droit' => 'Modifier', 'status_droit' => 'actif'],
            ['nom_droit' => 'Supprimer', 'status_droit' => 'inactif'],
        ];

        foreach ($droits as $droit) {
            Droit::create($droit);
        }
        //
    }
}
