<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ElementPaillasseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de l\'élément est requis.',
            'name.string' => 'Le nom de l\'élément doit être une chaîne de caractères.',
            'name.max' => 'Le nom de l\'élément ne peut pas dépasser 255 caractères.',
            'unit.string' => 'L\'unité de l\'élément doit être une chaîne de caractères.',
            'unit.max' => 'L\'unité de l\'élément ne peut pas dépasser 255 caractères.',
            'type_result_id.required' => 'Le type de résultat est requis.',
            'type_result_id.exists' => 'Le type de résultat n\'existe pas.',
            'examen_id.required' => 'L\'examen est requis.',
            'examen_id.exists' => 'L\'examen n\'existe pas.',
            'normal_values.*.populate_id.exists' => 'Le groupe de population n\'existe pas.',
            'normal_values.*.value.required' => 'La valeur est requise.',
            'normal_values.*.value.numeric' => 'La valeur doit être numérique.',
            'normal_values.*.value_max.numeric' => 'La valeur maximale doit être numérique.',
            'normal_values.*.sign.required' => 'Le signe est requis.',
            'normal_values.*.sign.in' => 'Le signe doit être : =, >, >=, <=, <.',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'unit' => 'nullable|string|max:255',
            'numero_order' => 'nullable|integer',
            'type_result_id' => 'required|exists:type_results,id',
            'examen_id' => 'required|exists:examens,id',
            'normal_values' => ['nullable', 'array'],
            'normal_values.*.populate_id' => ['exists:groupe_populations,id'],
            'normal_values.*.value' => ['required', 'numeric'],
            'normal_values.*.value_max' => ['nullable', 'numeric'],
            'normal_values.*.sign' => ['required', 'in:=,>,>=,<=,<'],
        ];
    }
}
