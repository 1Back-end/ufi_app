<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExamenRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', Rule::unique('examens', 'code')->ignore($this->route('examen'))],
            'name' => ['required'],
            'price' => ['required', 'numeric'],
            'b' => ['required'],
            'b1' => ['required'],
            'renderer_duration' => ['nullable', 'integer'],
            'name_abrege' => ['nullable'],
            'prelevement_unit' => ['nullable', 'numeric'],
            'name1' => ['nullable'],
            'name2' => ['nullable'],
            'name3' => ['nullable'],
            'name4' => ['nullable'],
            'tube_prelevement_id' => ['required', 'exists:tube_prelevements,id'],
            'type_prelevement_id' => ['required', 'exists:type_prelevements,id'],
            'paillasse_id' => ['required', 'exists:paillasses,id'],
            'sub_family_exam_id' => ['required', 'exists:sub_family_exams,id'],
            'kb_prelevement_id' => ['required', 'exists:kb_prelevements,id'],
            'technique_analysis' => ['nullable', 'array'],
            'technique_analysis.*.id' => ['nullable', 'exists:analysis_techniques,id'],
            'technique_analysis.*.default' => ['nullable', 'boolean'],
            'elements' => ['required', 'array'],
            'elements.*.name' => ['required'],
            'elements.*.type_result_id' => ['required', 'exists:type_results,id'],
            'elements.*.cat_predefined_list_id' => ['nullable', 'exists:cat_predefined_lists,id'],
            'elements.*.numero_order' => ['required', 'integer'],
            'elements.*.unit' => ['nullable'],
            'elements.*.indent' => ['required', 'integer', 'min:0', 'max:5'],
            'elements.*.element_paillasses_id' => ['nullable'],
            'elements.*.predefined_list_id' => ['nullable'],
            'elements.*.normal_values' => ['nullable', 'array'],
            'elements.*.normal_values.*.populate_id' => ['nullable', 'exists:groupe_populations,id'],
            'elements.*.normal_values.*.value' => ['nullable', 'numeric'],
            'elements.*.normal_values.*.value_max' => ['nullable', 'numeric'],
            'elements.*.normal_values.*.sign' => ['nullable', 'in:=,>,>=,<=,<,[]'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __("Le nom de l'examen est obligatoire."),
            'price.required' => __("Le prix de l'examen est obligatoire."),
            'b.required' => __("Le b est requis !"),
            'b1.required' => __("b1 est requis."),
            'tube_prelevement_id.required' => __("Le tube de prélèvement est requis !"),
            'tube_prelevement_id.exists' => __("Vous devez choisir un tube de prélèvement valide."),
            'type_prelevement_id.required' => __("Vous devez choisir un type de prélèvement."),
            'type_prelevement_id.exists' => __("Ce type de prélèvement n'existe pas !"),
            'paillasse_id.required' => __("Vous devez choisir un paillasse."),
            'paillasse_id.exists' => __("Vous devez choisir un paillasse vailde."),
            'sub_family_exam_id.required' => __("Vous devez choisir une sous famille d'examen."),
            'sub_family_exam_id.exists' => __("Vous devez choisir une sous famille d'examen valide."),
            'kb_prelevement_id.required' => __("Vous devez choisir un kb de prélèvement."),
            'kb_prelevement_id.exists' => __("Vous devez choisir un kb de prélèvement valide."),
            'technique_analysis.required' => __("Les techniques d'analyse sont requises."),
            'technique_analysis.*.id.required' => __("L'ID de la technique d'analyse est requis."),
            'technique_analysis.*.id.exists' => __("La technique d'analyse n'existe pas."),
            'technique_analysis.*.default.required' => __("Le champ 'default' est requis pour la technique d'analyse."),
            'elements.required' => __("Les éléments de l'examen sont requis."),
            'elements.*.name.required' => __("Le nom de l'élément est requis."),
            'elements.*.type_result_id.required' => __("Le type de résultat est requis."),
            'elements.*.type_result_id.exists' => __("Le type de résultat n'existe pas."),
            'elements.*.numero_order.required' => __("Le numéro d'ordre de l'élément est requis."),
            'elements.*.numero_order.integer' => __("Le numéro d'ordre de l'élément doit être un entier."),
            'elements.*.unit.required' => __("L'unité de l'élément est requise."),
            'elements.*.normal_values.required' => __("Les valeurs normales de l'élément sont requises."),
            'elements.*.normal_values.*.populate_id.required' => __("L'ID de la population est requis."),
            'elements.*.normal_values.*.populate_id.exists' => __("La population n'existe pas."),
            'elements.*.normal_values.*.value.required' => __("La valeur normale est requise."),
            'elements.*.normal_values.*.value.numeric' => __("La valeur normale doit être un nombre."),
            'elements.*.normal_values.*.value_max.numeric' => __("La valeur maximale normale doit être un nombre."),
            'elements.*.normal_values.*.sign.required' => __("Le signe de la valeur normale est requis."),
            'elements.*.normal_values.*.sign.in' => __("Le signe de la valeur normale doit être l'un des suivants : =, >, >=, <=, <, []."),
        ];
    }
}
