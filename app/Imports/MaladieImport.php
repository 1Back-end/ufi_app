<?php

namespace App\Imports;

use App\Models\ClasseMaladie;
use App\Models\GroupeMaladie;
use App\Models\Maladie;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;

class MaladieImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        ini_set('max_execution_time', 300); // augmente à 5 minutes

        $classes = ClasseMaladie::all()->keyBy('code');
        $groupes = GroupeMaladie::all()->keyBy('code');
        $auth = auth()->user();

        $rows->shift(); // Ignore headers

        foreach ($rows as $index => $row) {
            $codeClasse = trim((string) ($row[0] ?? ''));
            $codeGroupe = trim((string) ($row[1] ?? ''));
            $codeNosologie = trim((string) ($row[2] ?? ''));
            $nosologie = trim((string) ($row[3] ?? ''));

            if (empty($codeClasse) || empty($codeGroupe) || empty($codeNosologie) || empty($nosologie)) {
                Log::warning("Ligne $index : Données incomplètes");
                continue;
            }

            $classe = $classes->get($codeClasse);
            $groupe = $groupes->get($codeGroupe);

            if (!$classe || !$groupe) {
                Log::warning("Ligne $index : Classe ou groupe introuvable");
                continue;
            }

            Maladie::updateOrCreate(
                ['code' => $codeNosologie],
                [
                    'classe_maladie_id' => $classe->id,
                    'groupe_maladie_id' => $groupe->id,
                    'name' => $nosologie,
                    'updated_by' => $auth->id ?? null,
                    'created_by' => Maladie::where('code', $codeNosologie)->exists()
                        ? Maladie::where('code', $codeNosologie)->value('created_by')
                        : ($auth->id ?? null),
                ]
            );
        }
    }



}
