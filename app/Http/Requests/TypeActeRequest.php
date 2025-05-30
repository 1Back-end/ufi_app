<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TypeActeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required'],
            'k_modulateur' => ['required', 'integer'],
            'b' => ['required', 'integer'],
            'b1' => ['required', 'integer'],
        ];
    }
}
