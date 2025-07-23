<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CatPredefinedListRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required'],
            'predefined_lists' => ['nullable', 'array'],
            'predefined_lists.*.name' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom est obligatoire.',
            'predefined_lists.*.name.required' => 'Le nom de la liste est obligatoire .',
        ];
    }
}
