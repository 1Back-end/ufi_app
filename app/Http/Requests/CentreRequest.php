<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CentreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'unique:centres,name'],
            'short_name' => ['required'],
            'address' => ['required'],
            'tel' => ['required'],
            'tel2' => ['nullable'],
            'contribuable' => ['required'],
            'registre_commerce' => ['required'],
            'autorisation' => ['required'],
            'town' => ['nullable'],
            'fax' => ['nullable'],
            'email' => ['nullable', 'email', 'max:254'],
            'website' => ['nullable'],
            'logo' => ['nullable', 'file', 'max:2048', 'mimes:jpg,jpeg,png'],
        ];
    }
}
