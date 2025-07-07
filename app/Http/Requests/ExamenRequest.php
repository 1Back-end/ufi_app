<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExamenRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required'],
            'price' => ['required', 'numeric'],
            'b' => ['required'],
            'b1' => ['required'],
            'renderer_duration' => ['nullable', 'integer'],
            'name_abrege' => ['nullable'],
            'prelevement_unit' => ['nullable', 'numeric'],
            'name1' => ['nullable'],
            'name2' => ['nullable'],
            'name2' => ['nullable'],
            'name3' => ['nullable'],
            'name4' => ['nullable'],
            'tube_prelevement_id' => ['required', 'exists:tube_prelevements,id'],
            'type_prelevement_id' => ['required', 'exists:type_prelevements,id'],
            'paillasse_id' => ['required', 'exists:paillasses,id'],
            'sub_family_exam_id' => ['required', 'exists:sub_family_exams,id'],
            'kb_prelevement_id' => ['required', 'exists:kb_prelevements,id'],
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
        ];
    }
}
