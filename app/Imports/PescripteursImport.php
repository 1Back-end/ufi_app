<?php

namespace App\Imports;

use App\Models\Consultant;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Faker\Factory as Faker;
use App\Models\Titre;
use App\Models\Specialite;
use App\Models\Service_Hopital;
use App\Models\Hopital;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PescripteursImport implements ToModel, WithHeadingRow
{
    public function model(array $row) {
        if ($row['supprimer_ouinon'] == 'Supprimer') return;

        $hopital = Hopital::first();
        $authId = auth()->id();

        $titre = Titre::firstOrCreate([
            'nom_titre' => $row['titre']
        ], [
            'nom_titre' => $row['titre'],
            'abbreviation_titre' => $row['titre'],
            'created_by' => $authId,
            'updated_by' => $authId
        ]);

        $consultant = DB::connection("old-sql")->table('consultants')
            ->where('ref', $row['reference'])
            ->first();

        return new Consultant([
            'ref' => $row['reference'],
            'code_hopi' => $hopital->id,
            'code_service_hopi' => $consultant->code_service_hopi,
            'code_specialite' => $consultant->code_specialite,
            'code_titre' => $titre->id,
            'nom' => $row['nom'],
            'prenom' => $row['prenom'],
            'nomcomplet' => $row['nom_complet'],
            'tel' => $row['telephone_principal'] ?? Faker::create()->phoneNumber(),
            'email' => $row['email'] ?? 'FAKER-' . Faker::create()->unique()->email,
            'type' => $row['type_consultant'],
            'created_by' => $authId,
            'updated_by' => $authId,
        ]);
    }
}
