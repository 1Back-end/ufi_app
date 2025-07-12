<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryElementResultRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', Rule::unique('category_element_results', 'code')->ignore($this->category_element_result)],
            'name' => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Le code est obligatoire',
            'code.unique' => 'Le code doit être unique',
            'name.required' => 'Le nom est obligatoire',
        ];
    }
}
