<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KbPrelevementRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required'],
            'name' => ['required'],
            'amount' => ['required', 'numeric', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => __("Le code est requis"),
            'name.required' => __("Le nom est requis"),
            "amount.required" => __("Le montant est requis"),
        ];
    }
}
