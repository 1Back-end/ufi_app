<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PrestationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'prise_charge_id' => ['nullable', 'integer'],
            'client_id' => ['required', 'exists:clients,id'],
            'consultant_id' => ['required', 'exists:consultants,id'],
            'assureur_id' => ['nullable', 'integer', 'exists:assureurs,id'],
            'payable' => ['boolean'],
            'payable_by' => ['required', 'exists:clients,id'],
            'programmation_date' => ['required', 'date'],
        ];
    }
}
