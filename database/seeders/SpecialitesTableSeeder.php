<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Specialite;
use App\Models\User;
class SpecialitesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {// Récupérer un utilisateur existant, tu peux aussi en créer un si nécessaire
        $user = User::first(); // Utiliser le premier utilisateur disponible

        // Vérifie si un utilisateur existe dans la base de données
        if ($user) {
            Specialite::create([
                'nom_specialite' => 'Cardiologie',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            Specialite::create([
                'nom_specialite' => 'Pédiatrie',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            Specialite::create([
                'nom_specialite' => 'Orthopédie',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        } else {
            echo "Aucun utilisateur trouvé dans la base de données.";
        }


        //
    }
}
