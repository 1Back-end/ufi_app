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

        foreach ($rows as $index => $row) {

            $nom_presc = trim((string) ($row[0] ?? ''));
            if (empty($nom_presc)) continue;

            $tel_presc = trim((string) ($row[1] ?? $faker->phoneNumber()));
            $titre_presc = trim((string) ($row[2] ?? 'RAS'));
            $specialite_nom = trim((string) ($row[3] ?? 'RAS'));
            $service_nom = trim((string) ($row[4] ?? 'RAS'));
            $email_presc = trim((string) ($row[5] ?? 'noemail_'.$index.'@example.com'));

            // Création ou récupération des références
            $titre = Titre::firstOrCreate(['nom_titre' => $titre_presc], [
                'abbreviation_titre' => $titre_presc,
                'created_by' => $authId,
                'updated_by' => $authId
            ]);

            $specialite = Specialite::firstOrCreate(['nom_specialite' => $specialite_nom], [
                'created_by' => $authId,
                'updated_by' => $authId
            ]);

            $service_hopital = Service_Hopital::firstOrCreate(['nom_service_hopi' => $service_nom], [
                'created_by' => $authId,
                'updated_by' => $authId
            ]);

            $hopital = Hopital::first(); // si tu as plusieurs hopitaux, il faut déterminer lequel utiliser pour chaque ligne

            // Si email existe déjà, génère un email temporaire pour éviter conflit
            if (Consultant::where('email', $email_presc)->exists()) {
                $email_presc = 'dup_'.$index.'_'.$faker->unique()->safeEmail();
            }

            // Création du consultant
            Consultant::create([
                'code_hopi' => $hopital->id,
                'code_service_hopi' => $service_hopital->id,
                'code_specialite' => $specialite->id,
                'code_titre' => $titre->id,
                'nom' => $nom_presc,
                'prenom' => $nom_presc,
                'nomcomplet' => $titre_presc.' '.$nom_presc,
                'tel' => $tel_presc,
                'email' => $email_presc,
                'type' => 'Interne',
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
