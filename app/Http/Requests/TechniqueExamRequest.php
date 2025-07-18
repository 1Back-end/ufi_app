<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TechniqueExamRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required'],
            'analysis_technique_id' => ['required', 'exists:analysis_techniques,id'],
            'type' => ['required', 'in:default,no'],
            'examen_id' => ['required', 'exists:examens,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Le code est obligatoire.',
            'analysis_technique_id.required' => 'L\'analyse technique est obligatoire.',
            'analysis_technique_id.exists' => 'L\'analyse technique n\'existe pas.',
            'type.required' => 'Le type est obligatoire.',
            'type.in' => 'Le type doit être "Défaut" ou "Non".',
            'examen_id.required' => 'L\'examen est obligatoire.',
            'examen_id.exists' => 'L\'examen n\'existe pas.',
        ];
    }
}
