<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GroupePopulationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required'],
            'agemin' => ['required', 'integer', 'min:0', 'max:' . 100 * 12],
            'agemax' => ['nullable', 'integer', 'min:0', 'max:' . 100 * 12,],
            'sex_id' => ['required', 'exists:sexes,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __("Le nom est obligatoire."),
            'agemin.required' => __("L'age minimum est obligatoire."),
            'agemin.min' => __("L'age min doit être au minimim 0."),
            'agemin.max' => __("L'age max doit être au maximum 100."),
            'agemax.min' => __("l'age maximum doit être au minimum 0"),
            'agemax.max' => __("L'age maxi doit être au maximum 100"),
            'sex_id.required' => __("Le sexe est obligatoire."),
            'sex_id.exists' => __("Le sexe n'existe pas."),
        ];
    }
}
