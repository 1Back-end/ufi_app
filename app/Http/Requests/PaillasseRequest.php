<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaillasseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', Rule::unique('paillasses', 'code')->ignore($this->route('paillasse'))],
            'name' => ['required'],
            'description' => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Le code est requis',
            'code.unique' => 'Le code doit être unique',
            'name.required' => 'Le nom est requis',
            'description.required' => 'La description est requise',
        ];
    }
}
