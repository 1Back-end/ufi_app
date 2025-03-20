<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Unique;

class StatusFamilialeRequest extends FormRequest
{
    public function rules(): array
    {
        if ($this->isMethod('PUT')) {
            return [
                'description_statusfam' => [
                    'required',
                    (new Unique('status_familiales', 'description_statusfam'))->ignore($this->route('status_familiale')),
                ],
            ];
        }

        return [
            'description_statusfam' => ['required', 'unique:status_familiales,description_statusfam'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
