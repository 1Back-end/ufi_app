<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Service_Hopital;
use App\Models\User;
class ServiceHopitalsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer un utilisateur existant
        $user = User::first(); // Utilise le premier utilisateur disponible

        // Vérifier si un utilisateur existe
        if ($user) {
            // Ajouter des services hospitaliers avec des relations create_by_service_hopi et update_by_service_hopi
            Service_Hopital::create([
                'nom_service_hopi' => 'Cardiologie',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            Service_Hopital::create([
                'nom_service_hopi' => 'Pédiatrie',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            Service_Hopital::create([
                'nom_service_hopi' => 'Chirurgie Générale',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        } else {
            echo "Aucun utilisateur trouvé dans la base de données.";
        }
        //
    }
}
