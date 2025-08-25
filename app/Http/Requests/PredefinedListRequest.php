<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PredefinedListRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required'],
            'slug' => ['required'],
            'cat_predefined_list_id' => ['required', 'exists:cat_predefined_lists'],
            'show' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom est obligatoire.',
            'slug.required' => 'Le slug est obligatoire.',
            'cat_predefined_list_id.required' => 'La catégorie est obligatoire.',
            'cat_predefined_list_id.exists' => 'La catégorie n\'existe pas.',
            'show.required' => 'Le champ "Afficher" est obligatoire.',
        ];
    }
}
