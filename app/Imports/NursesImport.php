<?php

namespace App\Imports;

use App\Models\Nurse;
use Faker\Factory as Faker;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class NursesImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        ini_set('max_execution_time', 300);
        $authId = auth()->id();
        $faker = Faker::create();

        foreach ($rows as $index => $row) {
            // Ignorer les lignes vides
            if (empty($row['nom']) && empty($row['prenom']) && empty($row['email'])) {
                continue;
            }

            $nom        = trim((string) ($row['nom'] ?? ''));
            $prenom     = trim((string) ($row['prenom'] ?? ''));
            $email      = trim((string) ($row['email'] ?? ''));
            $telephone  = trim((string) ($row['telephone'] ?? ''));
            $specialite = trim((string) ($row['specialite'] ?? ''));
            $adresse    = trim((string) ($row['adresse'] ?? ''));

            // Si email existe déjà ou vide → Faker génère un email
            if (Nurse::where('email', $email)->exists() || empty($email)) {
                $email = $faker->unique()->safeEmail();
            }

            // Faker remplit uniquement les champs manquants
            if (empty($adresse)) {
                $adresse = $faker->address();
            }
            if (empty($specialite)) {
                $specialite = $faker->word();
            }
            if (empty($telephone)) {
                $telephone = $faker->phoneNumber();
            }

            Nurse::create([
                "nom"        => $nom,
                "prenom"     => $prenom,
                "email"      => $email,
                "telephone"  => $telephone,
                "specialite" => $specialite,
                "adresse"    => $adresse,
                "created_by" => $authId,
                "updated_by" => $authId,
            ]);
        }
    }
}
