<?php

namespace App\Imports;

use App\Models\AnalysisTechnique;
use App\Models\CatPredefinedList;
use App\Models\ElementPaillasse;
use App\Models\Examen;
use App\Models\FamilyExam;
use App\Models\GroupePopulation;
use App\Models\KbPrelevement;
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
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class UploadExistingElementPaillasse implements ToCollection, withHeadingRow
{
    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection): void
    {
        foreach ($collection as $item) {
            $item = $item->toArray();

            $examen = Examen::where('code_exam', $item['code_ex'])->first();
            if (!$examen) {
                continue;
            }

            $valeurs = $item['liste_valeur'] && $item['liste_valeur'] != 'NULL'
                ? explode('#', $item['liste_valeur'])
                : [];

            if(count($valeurs)) {
                $valeurs = Arr::where($valeurs, fn($valeur) => $valeur != 'NULL');
            }

            $cat = null;
            if (count($valeurs) > 0) {
                // Catégories d’éléments prédéfinies
                $cat = CatPredefinedList::updateOrCreate([
                    'slug' => str($item['nature']  .'-'. $item['num'])->slug()->upper(),
                ], [
                    'slug' => str($item['nature']  .'-'. $item['num'])->slug()->upper(),
                    'name' => $item['nature']  .'-'. $item['num'],
                ]);

                foreach ($valeurs as $valeur) {
                    if (trim($valeur)) {
                        PredefinedList::updateOrCreate([
                            'slug' => str($valeur  .'-'. $item['num'])->slug()->upper(),
                        ], [
                            'slug' => str($valeur  .'-'. $item['num'])->slug()->upper(),
                            'name' => $valeur,
                            'cat_predefined_list_id' => $cat->id,
                        ]);
                    }
                }
            }

            // Type de résultat
            $typeResult = null;
            if ($item['nature'] && $item['nature'] != 'NULL') {
                $type = str($item['nature'])->slug()->lower();

                $input = null;
                if ($type == 'groupe') $input = 'group';
                if ($type == 'interligne') $input = 'inline';
                if ($type == 'commentaire') $input = 'comment';

                $typeResult = TypeResult::updateOrCreate([
                    'code' => str($item['nature']  .'-'. $item['num'])->slug()->upper(),
                ], [
                    'code' => str($item['nature']  .'-'. $item['num'])->slug()->upper(),
                    'name' => $item['nature']  .'-'. $item['num'],
                    'accept_saisi_user' => true,
                    'afficher_result' => true,
                    'type' => count($valeurs) > 0
                        ? 'select'
                        : ($input ?? 'text'),
                ]);
            }

            $dep = null;
            if ($item['num_s_ex_dep'] && $item['num_s_ex_dep'] != '0' && $item['num_s_ex_dep'] != 'NULL') {
                $dep = ElementPaillasse::whereLike('num', $item['num_s_ex_dep'])->first();
            }

            $element = $examen->elementPaillasses()->create([
                'name' => $item['element_paillasse'] ?? $item['nom_examen'],
                'unit' => $item['unite_ex'] && $item['unite_ex'] != 'NULL' ? $item['unite_ex'] : '',
                'numero_order' => $item['numord'] && $item['numord'] != 'NULL' ? $item['numord'] : 1,
                'cat_predefined_list_id' => $cat?->id,
                'type_result_id' => $typeResult?->id,
                'indent' => $item['tabul'] && $item['tabul'] != 'NULL' ? $item['tabul'] : 1,
                'num' => $item['num'],
                'element_paillasses_id' => $dep?->id
            ]);

            // Valeurs normales
            $this->setGroupPopulations($element, $item['valinfhoe'], $item['valmaxhoe'], 'homme');

            $this->setGroupPopulations($element, $item['valinffem'], $item['valmaxfem'], 'femme');

            $this->setGroupPopulations($element, $item['valinfenf'], $item['valmaxenf'], 'enfant-masculin');
            $this->setGroupPopulations($element, $item['valinfenf'], $item['valmaxenf'], 'enfant-feminin');
        }
    }

    private function setGroupPopulations(ElementPaillasse $element, $min, $max, $codeGroup): void
    {
        if (($min == 'NULL' && $max == 'NULL') || (!$min && !$max) || ($min == '0' && $max == '0') || ($min == 'NULL' && $max == '0') || ($min == '0' && $max == 'NULL')) {
            return;
        }

        $group = GroupePopulation::firstOrCreate([
            'code' => $codeGroup
        ], [
            'code' => $codeGroup,
            'name' => str($codeGroup)->upper(),
            'sex_id' => Sexe::where('description_sex', $codeGroup == 'homme' ? 'Masculin' : 'Feminin')->first()->id,
        ]);

        if ($min && !$max) {
            if ($min == 'NULL') dd($min, $max, $element->name);
            $element->group_populations()->attach($group->id, [
                'value' => $min,
                'sign' => '>=',
            ]);
        }

        if (!$min && $max) {
            if ($max == 'NULL') dd($max, $min);

            $element->group_populations()->attach($group->id, [
                'value' => $max,
                'sign' => '<=',
            ]);
        }

        if ($min && $max) {
            $element->group_populations()->attach($group->id, [
                'value' => $max,
                'sign' => '[]',
                'value_max' => $max
            ]);
        }
    }
}
