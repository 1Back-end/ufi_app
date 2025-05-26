<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Unique;

class PrefixeRequest extends FormRequest
{
    public function rules(): array
    {
        if ($this->isMethod('PUT')) {
            $uniqueRule = (new Unique('prefixes', 'prefixe'))->ignore($this->route('prefix'));
        }
        else {
            $uniqueRule = 'unique:prefixes,prefixe';
        }

        return [
            'prefixe' => ['required', $uniqueRule],
            'position' => ['required', 'integer', 'in:0,1,2'],
            'age_min' => ['nullable', 'integer', 'min:0', 'max:150'],
            'age_max' => ['nullable', 'integer', 'min:0', 'max:150'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
