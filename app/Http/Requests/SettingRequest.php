<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'key' => ['required'],
            'description' => ['nullable'],
            'value' => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'key.required' => 'La cle est requise',
            'value.required' => 'La valeur est requise',
        ];
    }
}
