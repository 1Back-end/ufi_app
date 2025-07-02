<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubFamilyExamRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', Rule::unique('sub_family_exams', 'code')->ignore($this->route('sub_family_exam'))],
            'name' => ['required'],
            'description' => ['nullable'],
            'family_exam_id' => ['required', 'exists:family_exams,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Le code est obligatoire.',
            'code.unique' => 'Le code doit être unique.',
            'name.required' => 'Le nom est obligatoire.',
            'description.required' => 'La description est obligatoire.',
            'family_exam_id.required' => 'L\'examen familial est obligatoire.',
            'family_exam_id.exists' => 'L\'examen familial n\'existe pas.',
        ];
    }
}
