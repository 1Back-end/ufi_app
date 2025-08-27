<?php

namespace App\Imports;

use App\Models\AnalysisTechnique;
use App\Models\CatPredefinedList;
use App\Models\ElementPaillasse;
use App\Models\Examen;
use App\Models\FamilyExam;
use App\Models\GroupePopulation;
use App\Models\KbPrelevement;
use App\Models\Paillasse;
use App\Models\PredefinedList;
use App\Models\Sexe;
use App\Models\SubFamilyExam;
use App\Models\TubePrelevement;
use App\Models\TypeResult;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UploadExistingExamen implements ToArray, withHeadingRow
{
    /**
     * @param array $array
     */
    public function array(array $array): void
    {
        foreach ($array as $item) {
            //            dd($item);

            if (! $item['nom_ex']) continue;

            $tech = null;
            if ($item['technique_par_defaut'] && $item['technique_par_defaut'] != 'NULL') {
                $tech = AnalysisTechnique::updateOrCreate([
                    'code' => str($item['technique_par_defaut'])->slug()->upper(),
                ], [
                    'code' => str($item['technique_par_defaut'])->slug()->upper(),
                    'name' => $item['technique_par_defaut'],
                    'detail' => $item['technique_par_defaut'],
                    'duration' => 30,
                ]);
            }

            $codeExamen = $item['codexamen'] && $item['codexamen'] != 'NULL'
                ? str($item['codexamen'])->slug()->upper()
                : "EX" . str($item['code_ex'])->padLeft(12, '0');

            $exam = [
                "12" => "HMA",
                "13" => "CBS",
                "14" => "CBU",
                "15" => "ISE",
                "16" => "BAC",
                "17" => "PAR",
                "18" => "HMT",
                "19" => "DIV",
                "24" => "HMO",
                "25" => "BDL",
                "26" => "MDP",
                "27" => "TIR",
                "28" => "THE",
                "29" => "END",
                "30" => "SVI",
                "31" => "BRE",
                "32" => "BLI",
                "33" => "BMO",
                "34" => "ANAP",
            ];

            $family = isset($exam[$item['code_fam']])
                ? FamilyExam::where('code', $exam[$item['code_fam']])->first()
                : null;

            $paillasse = isset($exam[$item['code_fam']])
                ? Paillasse::where('code', $exam[$item['code_fam']])->first()
                : null;

            $kbCodes = [
                '1' => 800,
                '1.5' => 1200,
                '3' => 2400
            ];

            // Create Examen
            $examen = Examen::updateOrCreate([
                'code' => $codeExamen,
            ], [
                'code' => $codeExamen,
                'name' => $item['nom_ex'],
                'price' => $item['prix_unitaire'],
                'b' => $item['b'],
                'b1' => $item['b1'],
                'renderer_duration' => $item['delai'] && $item['delai'] != 'NULL' ? $item['delai'] : null,
                'name_abrege' => explode('(', $item['nom_ex']) && count(explode('(', $item['nom_ex'])) == 2
                    ? (explode(')', explode('(', $item['nom_ex'])[1]) ? explode(')', explode('(', $item['nom_ex'])[1])[0] : $item['nom_ex'])
                    : $item['nom_ex'],
                'name1' => $item['nom_ex1'],
                'name2' => $item['nom_ex2'],
                'name3' => $item['nom_ex3'],
                'name4' => $item['nom_ex4'],
                'tube_prelevement_id' => $item['tube_prelevement'] && $item['tube_prelevement'] != 'NULL'
                    ? TubePrelevement::where('code', str($item['tube_prelevement'])->slug('')->upper())->first()?->id
                    : null,
                'type_prelevement_id' => null,
                'paillasse_id' => $paillasse?->id,
                'sub_family_exam_id' => $family?->subFamilyExam?->id,
                'kb_prelevement_id' => isset($kbCodes[(string)$item['kb']])
                    ? KbPrelevement::where('code', "KB" . $kbCodes[(string)$item['kb']])->first()?->id
                    : null,
                'code_exam' => $item['code_ex'],
            ]);

            if (!$item['sous'] || $item['sous'] == 'NULL') {
                $valeurs = $item['valeur_possible_des_resultats'] && $item['valeur_possible_des_resultats'] != 'NULL'
                    ? explode('#', $item['valeur_possible_des_resultats'])
                    : [];

                if(count($valeurs)) {
                    $valeurs = Arr::where($valeurs, fn($valeur) => $valeur != 'NULL');
                }

                $cat = null;
                if (count($valeurs) > 0) {
                    // Catégories d’éléments prédéfinies
                    $cat = CatPredefinedList::updateOrCreate([
                        'slug' => str($item['type_result'] . '-' . $item['nom_ex'])->slug()->upper(),
                    ], [
                        'slug' => str($item['type_result'] . '-' . $item['nom_ex'])->slug()->upper(),
                        'name' => $item['type_result'] . '-' . $item['nom_ex'],
                    ]);

                    foreach ($valeurs as $valeur) {
                        if (trim($valeur)) {
                            dump(Str::endsWith('*', $valeur) ? Str::replace('*', '', $valeur) : $valeur);

                            PredefinedList::updateOrCreate([
                                'slug' => str($valeur .'-'. $item['nom_ex'])->slug()->upper(),
                            ], [
                                'slug' => str($valeur .'-'. $item['nom_ex'])->slug()->upper(),
                                'name' => Str::endsWith('*', $valeur) ? Str::replace('*', '', $valeur) : $valeur,
                                'cat_predefined_list_id' => $cat->id,
                                'show' => !Str::endsWith('*', $valeur)
                            ]);
                        }
                    }
                }

                // Type de résultat
                $typeResult = null;
                if ($item['type_result'] && $item['type_result'] != 'NULL') {
                    $type = str($item['type_result'])->slug()->lower();

                    $input = null;
                    if ($type == 'groupe') $input = 'group';
                    if ($type == 'interligne') $input = 'inline';
                    if ($type == 'commentaire') $input = 'comment';

                    $typeResult = TypeResult::updateOrCreate([
                        'code' => str($item['type_result']  .'-'. $item['num'])->slug()->upper(),
                    ], [
                        'code' => str($item['type_result']  .'-'. $item['num'])->slug()->upper(),
                        'name' => $item['type_result'] . '-' . $item['nom_ex'],
                        'accept_saisi_user' => true,
                        'afficher_result' => true,
                        'type' =>  count($valeurs) > 0
                            ? 'select'
                            : ($input ?? 'text'),
                    ]);
                }

                $element = $examen->elementPaillasses()->create([
                    'name' => $item['nom_ex'],
                    'unit' => $item['unitex'],
                    'numero_order' => $item['num'] && $item['num'] != 'NULL' ? $item['num'] : 1,
                    'cat_predefined_list_id' => $cat?->id,
                    'type_result_id' => $typeResult?->id,
                    'indent' => 1,
                ]);

                // Valeurs normales
                if ($item['valhommeex'] && $item['valhommeex'] != 'NULL') {
                    $this->setGroupPopulations($element, $item, 'valhommeex', 'homme');
                }

                if ($item['valfemmeex'] && $item['valfemmeex'] != 'NULL') {
                    $this->setGroupPopulations($element, $item, 'valfemmeex', 'femme');
                }

                if ($item['valenfantex'] && $item['valenfantex'] != 'NULL') {
                    $this->setGroupPopulations($element, $item, 'valenfantex', 'enfant-masculin');
                    $this->setGroupPopulations($element, $item, 'valenfantex', 'enfant-feminin');
                }
            }

            // Associate Examen to Technique
            if ($tech) {
                $examen->techniqueAnalysis()->syncWithPivotValues([$tech->id], [
                    'type' => 1
                ]);
            }
        }
    }

    private function setGroupPopulations(ElementPaillasse $element, $item, $index, $codeGroup): void
    {
        $group = GroupePopulation::firstOrCreate([
            'code' => $codeGroup
        ], [
            'code' => $codeGroup,
            'name' => str($codeGroup)->upper(),
            'sex_id' => Sexe::where('description_sex', $codeGroup == 'homme' ? 'Masculin' : 'Feminin')->first()->id,
        ]);

        if (str_contains($item[$index], '-')) {
            $values = explode('-', $item[$index]);

            $element->group_populations()->attach($group->id, [
                'value' => (float)trim($values[0]),
                'value_max' => (float)trim($values[1]),
                'sign' => '[]',
            ]);
        }

        if (str_contains($item[$index], '<')) {
            $values = explode('<', $item[$index]);

            $element->group_populations()->attach($group->id, [
                'value' => (float)trim($values[0]),
                'sign' => '<',
            ]);
        }
    }
}
