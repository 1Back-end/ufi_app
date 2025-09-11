<?php

namespace App\Imports;

use App\Models\Consultant;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Faker\Factory as Faker;
use App\Models\Titre;
use App\Models\Specialite;
use App\Models\Service_Hopital;
use App\Models\Hopital;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PescripteursImport implements ToCollection, WithHeadings
{
    public function collection(Collection $rows)
    {
        $authId = auth()->id();
        $faker = Faker::create();

        // Ignore la ligne d'en-tête
        $rows->shift();

        // Tableau pour vérifier les doublons dans le fichier
        $existing = [];

        foreach ($rows as $index => $row) {

            $nom_presc = trim((string) ($row[0] ?? ''));
            $prenom_presc = $nom_presc;

            // Convertir "NULL" en vide
            $nom_presc = strtoupper($nom_presc) === 'NULL' ? '' : $nom_presc;
            $email_presc = trim((string) ($row[5] ?? ''));

            $email_presc = strtoupper($email_presc) === 'NULL' || empty($email_presc)
                ? 'noemail_'.$index.'@example.com'
                : $email_presc;

            $tel_presc = trim((string) ($row[1] ?? ''));
            $tel_presc = strtoupper($tel_presc) === 'NULL' || empty($tel_presc)
                ? $faker->phoneNumber()
                : $tel_presc;

            // Ignorer les lignes si nom ou email vide
            if (empty($nom_presc) || empty($email_presc)) continue;

            $key = strtolower($nom_presc.'_'.$prenom_presc);

            // Ignorer si doublon dans le fichier
            if (in_array($key, $existing)) continue;
            $existing[] = $key;


            $titre_presc = trim((string) ($row[2] ?? 'RAS'));
            $specialite_nom = trim((string) ($row[3] ?? 'RAS'));
            $service_nom = trim((string) ($row[4] ?? 'RAS'));

            // Création ou récupération des références
            $titre = Titre::firstOrCreate(
                ['nom_titre' => $titre_presc],
                ['abbreviation_titre' => $titre_presc, 'created_by' => $authId, 'updated_by' => $authId]
            );

            $specialite = Specialite::firstOrCreate(
                ['nom_specialite' => $specialite_nom],
                ['created_by' => $authId, 'updated_by' => $authId]
            );

            $service_hopital = Service_Hopital::firstOrCreate(
                ['nom_service_hopi' => $service_nom],
                ['created_by' => $authId, 'updated_by' => $authId]
            );

            $hopital = Hopital::first();

            // Vérifier doublon dans la base avant création
            if (Consultant::where('nom', $nom_presc)->where('prenom', $prenom_presc)->exists()) {
                continue;
            }

            // Création du consultant
            Consultant::create([
                'code_hopi' => $hopital->id,
                'code_service_hopi' => $service_hopital->id,
                'code_specialite' => $specialite->id,
                'code_titre' => $titre->id,
                'nom' => $nom_presc,
                'prenom' => $prenom_presc,
                'nomcomplet' => $titre_presc.' '.$nom_presc,
                'tel' => $tel_presc,
                'email' => $email_presc,
                'type' => 'Externe',
                'created_by' => $authId,
                'updated_by' => $authId,
            ]);
        }
    }



    public function headings(): array
    {
        return [
            'nom_presc',
            'tel_presc',
            'Titre_presc',
            'specialite',
            'service',
            'email_presc',
        ];
    }
}
