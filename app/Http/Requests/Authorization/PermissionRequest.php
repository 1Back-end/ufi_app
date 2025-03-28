<?php

namespace App\Http\Requests\Authorization;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Unique;

class PermissionRequest extends FormRequest
{
    public function rules(): array
    {
        if ($this->isMethod('PUT')) {
            $uniqueName = (new Unique('permissions', 'name'))->ignore($this->route('permission'));
        }
        else {
            $uniqueName = 'unique:permissions,name';
        }

        return [
            'name' => ['required', $uniqueName],
            'description' => ['required'],
        ];
    }
}
