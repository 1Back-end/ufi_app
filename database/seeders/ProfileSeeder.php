<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Profile;
class ProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $profiles = [
            ['nom_profile' => 'Administrateur', 'status_profile' => 'actif', 'description_profile' => 'Gère le système'],
            ['nom_profile' => 'Utilisateur', 'status_profile' => 'actif', 'description_profile' => 'Accès limité'],
        ];

        foreach ($profiles as $profile) {
            Profile::create($profile);
        }
    }
}
