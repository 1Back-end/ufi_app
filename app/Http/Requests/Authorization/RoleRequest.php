<?php

namespace App\Http\Requests\Authorization;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Unique;

class RoleRequest extends FormRequest
{
    public function rules(): array
    {
        if ($this->isMethod('PUT')) {
            $uniqueRule = (new Unique('roles', 'name'))->ignore($this->route('role'));
        } else {
            $uniqueRule = 'unique:roles,name';
        }

        return [
            'name' => ['required', $uniqueRule],
            'description' => ['required'],
            'confidential' => ['nullable', 'boolean'],
            'accueil_url' => ['nullable'],
            'permissions' => ['nullable', 'array'],
            'permissions.*.id' => ['required', 'exists:permissions,id'],
            'permissions.*.centres' => ['nullable', 'array'],
            'permissions.*.centres.*.id' => ['required_with:permissions.*.centres', 'exists:centres,id'],
        ];
    }
}
