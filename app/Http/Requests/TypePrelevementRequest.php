<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TypePrelevementRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', 'unique:type_prelevements,code'],
            'name' => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Le code est requis',
            'code.unique' => 'Le code doit être unique',
            'name.required' => 'Le nom est requis',
        ];
    }
}
