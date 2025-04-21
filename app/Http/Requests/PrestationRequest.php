<?php

namespace App\Http\Requests;

use App\Enums\TypePrestation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class PrestationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'prise_charge_id' => ['nullable', 'exists:prise_en_charges,id'],
            'client_id' => ['required', 'exists:clients,id'],
            'consultant_id' => ['required', 'exists:consultants,id'],
            'payable_by' => ['nullable', 'exists:clients,id'],
            'programmation_date' => ['required', 'date'],
            'type' => ['required', new Enum(TypePrestation::class)],
            'actes' => ['nullable', 'array', 'required_if:type,' . TypePrestation::ACTES->value],
            'actes.*.id' => ['required_if:type,' . TypePrestation::ACTES->value, 'exists:actes,id'],
            'actes.*.remise' => ['min:0', 'integer'],
            'actes.*.quantity' => ['integer', 'required_if:type,' . TypePrestation::ACTES->value, 'min:1'],
            'actes.*.date_rdv' => ['required_if:type,' . TypePrestation::ACTES->value,]
        ];
    }
}
