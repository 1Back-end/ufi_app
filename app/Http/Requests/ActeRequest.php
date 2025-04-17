<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required'],
            'pu' => ['required', 'integer'],
            'type_acte_id' => ['required', 'exists:type_actes,id'],
            'delay' => ['required', 'integer'],
            'k_modulateur' => ['required', 'integer'],
            'b' => ['required', 'integer'],
            'b1' => ['required', 'integer'],
        ];
    }
}
