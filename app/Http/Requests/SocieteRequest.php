<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Unique;

class SocieteRequest extends FormRequest
{
    public function rules(): array
    {
        if ($this->isMethod('PUT')) {
            $uniqueRule = (new Unique('societes', 'nom_soc_cli'))->ignore($this->route('societe'));
        }
        else {
            $uniqueRule = 'unique:societes,nom_soc_cli';
        }


        return [
            'nom_soc_cli' => ['required', $uniqueRule],
            'tel_soc_cli' => ['nullable'],
            'adress_soc_cli' => ['nullable'],
            'num_contrib_soc_cli' => ['nullable'],
            'email_soc_cli' => ['nullable'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
