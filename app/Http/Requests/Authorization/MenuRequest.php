<?php

namespace App\Http\Requests\Authorization;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Unique;

class MenuRequest extends FormRequest
{
    public function rules(): array
    {
        if ($this->isMethod('PUT')) {
            $uniqueRule = (new Unique('menus', 'libelle'))->ignore($this->route('menu'));
        }
        else {
            $uniqueRule = 'unique:menu,libelle';
        }

        return [
            'parent' => ['nullable'],
            'path' => ['required'],
            'libelle' => ['required', $uniqueRule],
            'permission_ids' => ['array', 'nullable']
        ];
    }
}
