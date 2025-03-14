<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Centre;

class UserCentreTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();  // Utilise le premier utilisateur existant ou crée en un si nécessaire
        $centre = Centre::first();  // Utilise le premier centre existant ou crée en un si nécessaire

        $user->centres()->attach($centre->id);  // Ajoute une association
        //
    }
}
