<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnalysisTechniqueRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', Rule::unique('analysis_techniques', 'code')->ignore($this->route('analysis_technique'))],
            'name' => ['required'],
            'duration' => ['required', 'integer'],
            'detail' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Le code est requis.',
            'name.required' => 'Le nom est requis.',
            'duration.required' => 'La durée est requise.',
            'duration.integer' => 'La durée doit être un entier.',
            'detail.required' => 'Le détail est requis.',
        ];
    }
}
