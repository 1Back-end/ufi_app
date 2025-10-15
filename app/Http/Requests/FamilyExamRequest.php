<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FamilyExamRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', Rule::unique('family_exams', 'code')->ignore($this->route('family_exam'))],
            'order' => ['integer', 'min:0'],
            'name' => ['required'],
            'description' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Le code est requis',
            'name.required' => 'Le nom est requis',
            'description.required' => 'La description est requise',
        ];
    }
}
