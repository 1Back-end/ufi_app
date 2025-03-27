<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Titre;
use App\Models\User;
class TitresTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer un utilisateur existant, tu peux aussi en créer un si nécessaire
        $user = User::first(); // Utiliser le premier utilisateur disponible

        // Vérifie si un utilisateur existe dans la base de données
        if ($user) {
            Titre::create([
                'nom_titre' => 'Docteur',
                'abbreviation_titre' => 'Dr',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            Titre::create([
                'nom_titre' => 'Professeur',
                'abbreviation_titre' => 'Pr',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            Titre::create([
                'nom_titre' => 'Ingénieur',
                'abbreviation_titre' => 'Ing',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        } else {
            echo "Aucun utilisateur trouvé dans la base de données.";
        }

        //
    }
}
