<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TubePrelevementRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', Rule::unique('tube_prelevements', 'code')->ignore($this->route('tube_prelevement'))],
            'name' => ['required'],
        ];
    }

    public function messages()
    {
        return [
            'code.required' => 'Le code est obligatoire.',
            'code.unique' => 'Le code doit être unique.',
            'name.required' => 'Le nom est obligatoire.',
        ];
    }
}
