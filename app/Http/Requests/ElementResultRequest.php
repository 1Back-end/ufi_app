<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ElementResultRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', Rule::unique('element_results', 'code')->ignore($this->route('element_result'))],
            'name' => ['required'],
            'category_element_result_id' => ['required', 'exists:category_element_results,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Le code est obligatoire.',
            'code.unique' => 'Le code doit être unique.',
            'name.required' => 'Le nom est obligatoire.',
            'category_element_result_id.required' => 'La catégorie est obligatoire.',
            'category_element_result_id.exists' => 'La catégorie n\'existe pas.',
        ];
    }
}
