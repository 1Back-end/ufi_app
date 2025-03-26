<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Unique;

class StatusFamilialeRequest extends FormRequest
{
    public function rules(): array
    {
        if ($this->isMethod('PUT')) {
            $unique = (new Unique('status_familiales', 'description_statusfam'))->ignore($this->route('status_familiale'));
        }
        else{
            $unique = 'unique:status_familiales,description_statusfam';
        }

        return [
            'description_statusfam' => ['required', $unique],
            'sexes' => ['nullable'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
