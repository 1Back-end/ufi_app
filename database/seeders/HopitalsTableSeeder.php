<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Hopital;
use App\Models\User;
class HopitalsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer un utilisateur existant, tu peux aussi en créer un si nécessaire
        $user = User::first(); // Utilise le premier utilisateur disponible

        // Vérifier si un utilisateur existe dans la base de données
        if ($user) {
            Hopital::create([
                'nom_hopi' => 'Hôpital Central Yaoundé',
                'Abbreviation_hopi' => 'HCY',
                'addresse_hopi' => 'Yaoundé, Centre-ville, Cameroon',
                'create_by_hopi' => $user->id,
                'update_by_hopi' => $user->id,
            ]);

            Hopital::create([
                'nom_hopi' => 'Hôpital de District Douala',
                'Abbreviation_hopi' => 'HDD',
                'addresse_hopi' => 'Douala, Littoral, Cameroon',
                'create_by_hopi' => $user->id,
                'update_by_hopi' => $user->id,
            ]);

            Hopital::create([
                'nom_hopi' => 'Hôpital Général Bafoussam',
                'Abbreviation_hopi' => 'HGB',
                'addresse_hopi' => 'Bafoussam, Ouest, Cameroon',
                'create_by_hopi' => $user->id,
                'update_by_hopi' => $user->id,
            ]);
        } else {
            echo "Aucun utilisateur trouvé dans la base de données.";
        }
        //
    }
}
