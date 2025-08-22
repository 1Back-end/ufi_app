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
            'predefined_lists.*.id' => ['nullable'],
            'predefined_lists.*.name' => ['nullable', 'string'],
            'predefined_lists.*.show' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom est obligatoire.',
        ];
    }
}
