<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResultRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'data' => ['required', 'array'],
            'data.*.prestation_id' => ['required', 'exists:prestations,id'],
            'data.*.show' => ['boolean'],
            'data.*.results.*.element_paillasse_id' => ['required', 'exists:element_paillasses,id'],
            'data.*.results.*.groupe_population_id' => ['nullable', 'exists:groupe_populations,id'],
            'data.*.results.*.result_machine' => ['nullable'],
            'data.*.results.*.result_client' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'data.required' => __("Le resultat est obligatoire"),
            'data.*.prestation_id.required' => __("La prestation est obligatoire"),
            'data.*.results.*.element_paillasse_id.required' => __("L'element de paillasse est obligatoire"),
            'data.*.results.*.groupe_population_id.required' => __("Le groupe de population est obligatoire"),
        ];
    }
}
