<?php

namespace App\Http\Requests;

use App\Enums\TypeRegulation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class RegulationMethodRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required'],
            'description' => ['nullable'],
            'comment_required' => ['required', 'boolean'],
            'type_regulation' => ['nullable', new Enum(TypeRegulation::class)]
        ];
    }
}
