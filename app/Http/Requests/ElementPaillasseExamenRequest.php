<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ElementPaillasseExamenRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'unit' => 'nullable|string|max:255',
            'order_number' => 'nullable|integer',
            'category_element_result_id' => 'required|exists:category_element_results,id',
            'type_result_id' => 'required|exists:type_results,id',
        ];
    }
}
