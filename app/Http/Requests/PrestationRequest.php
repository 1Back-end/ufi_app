<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PrestationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'prise_charge_id' => ['nullable', 'integer'],
            'client_id' => ['required', 'exists:clients'],
            'consultant_id' => ['required', 'exists:consultants'],
            'assureur' => ['nullable', 'integer'],
            'payable' => ['boolean'],
            'payable_by' => ['required', 'exists:clients'],
            'programmation_date' => ['required', 'date'],
        ];
    }
}
