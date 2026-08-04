<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActeRequest extends FormRequest
{

    public function rules(): array
    {
        return [
            'name' => ['required'],
            'pu' => ['required', 'integer'],
            'type_acte_id' => ['required', 'exists:type_actes,id'],
            'delay' => ['required', 'integer'],
            'k_modulateur' => ['required', 'integer'],
            'b' => ['required', 'integer'],
            'b1' => ['required', 'integer'],
            'pu_assurance' => ['nullable', 'integer'],
            'code' => ['nullable', 'string'],
            'is_used_for_commission' => ['nullable', 'boolean'],
            'has_items' => ['nullable', 'boolean'],
            'items' => ['nullable', 'array'],
            'items.*.product_id' => ['required_with:items', 'exists:products,id'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
        ];
    }
}
