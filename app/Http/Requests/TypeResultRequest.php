<?php

namespace App\Http\Requests;

use App\Enums\InputType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class TypeResultRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', Rule::unique('type_results', 'code')->ignore($this->type_result)],
            'name' => ['required'],
            'accept_saisi_user' => ['boolean'],
            'afficher_result' => ['boolean'],
            'type' => ['required', new Enum(InputType::class)],
            'cat_predefined_list_id' => ['nullable', 'exists:cat_predefined_lists,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Le code est obligatoire',
            'code.unique' => 'Le code doit être unique',
            'name.required' => 'Le nom est obligatoire',
            'accept_saisi_user.boolean' => 'Le champ est obligatoire',
            'afficher_result.boolean' => 'Le champ est obligatoire',
            'type.required' => 'Le type est obligatoire',
            'cat_predefined_list_id.exists' => 'La catégorie n\'existe pas',
        ];
    }
}
