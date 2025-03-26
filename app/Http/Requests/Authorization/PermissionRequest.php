<?php

namespace App\Http\Requests\Authorization;

use Illuminate\Foundation\Http\FormRequest;

class PermissionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'description' => ['required'],
        ];
    }
}
