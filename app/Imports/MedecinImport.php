<?php

namespace App\Imports;

use App\Models\Consultant;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;
use Faker\Factory as Faker;
use App\Models\Titre;
use App\Models\Specialite;
use App\Models\Service_Hopital;
use App\Models\Hopital;

class MedecinImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        ini_set('max_execution_time', 300);
        $authId = auth()->id();
        $faker = Faker::create();

        // Supprimer la ligne d’en-tête
        $rows->shift();

        foreach ($rows as $index => $row) {
            $DESIGNATION   = trim((string) ($row[1] ?? ''));
            $Telephone_1   = trim((string) ($row[2] ?? ''));
            $SPECIALITE_1  = trim((string) ($row[5] ?? ''));

            // Récupère ou crée la spécialité
            $specialite = Specialite::firstOrCreate(
                ['nom_specialite' => $SPECIALITE_1],
                ['created_by' => $authId, 'updated_by' => $authId]
            );

            $hopital         = Hopital::first();
            $titre           = Titre::first();
            $service_hopital = Service_Hopital::first();

            // Génération email unique si besoin
            $email_presc = $faker->unique()->safeEmail();

            // Vérifier si le consultant existe déjà (par tel ou nom + prénom)
            $consultant = Consultant::where('tel', $Telephone_1)
                ->orWhere(function($q) use ($DESIGNATION) {
                    $q->where('nom', $DESIGNATION)
                        ->where('prenom', $DESIGNATION);
                })
                ->first();

            if ($consultant) {
                // 🔄 Mise à jour si trouvé
                $consultant->update([
                    'code_hopi'         => $hopital?->id,
                    'code_service_hopi' => $service_hopital?->id,
                    'code_specialite'   => $specialite->id,
                    'code_titre'        => $titre?->id,
                    'nom'               => $DESIGNATION,
                    'prenom'            => $DESIGNATION,
                    'nomcomplet'        => $titre?->libelle.' '.$DESIGNATION,
                    'tel'               => $Telephone_1,
                    'updated_by'        => $authId,
                ]);
            } else {
                // ➕ Création si inexistant
                Consultant::create([
                    'code_hopi'         => $hopital?->id,
                    'code_service_hopi' => $service_hopital?->id,
                    'code_specialite'   => $specialite->id,
                    'code_titre'        => $titre?->id,
                    'nom'               => $DESIGNATION,
                    'prenom'            => $DESIGNATION,
                    'nomcomplet'        => $titre?->libelle.' '.$DESIGNATION,
                    'tel'               => $Telephone_1,
                    'email'             => $email_presc,
                    'type'              => 'Interne',
                    'created_by'        => $authId,
                    'updated_by'        => $authId,
                ]);
            }
        }
    }
}
