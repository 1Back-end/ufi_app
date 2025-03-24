<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Unique;

class SexeRequest extends FormRequest
{
    public function rules(): array
    {
        // Validate Update for unique value
        if ($this->isMethod('PUT')) {
            $unique = (new Unique('sexes', 'description_sex'))->ignore($this->route('sex'));
        }
        else {
            $unique = 'unique:sexes,description_sex';
        }

        return [
            'description_sex' => [
                'required',
                $unique
            ],
            'prefixes' => ['nullable'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
