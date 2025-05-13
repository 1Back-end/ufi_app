<?php

namespace App\Http\Requests;

use App\Enums\TypeRegulation;
use App\Rules\ValidateAmountForRegulateFactureRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class RegulationRequest extends FormRequest
{
    public function rules(): array
    {
        if ($this->method() == 'PUT') {
            $requiredReason = 'required';
        } else {
            $requiredReason = 'nullable';
        }

        return [
            'facture_id' => ['required', 'exists:factures,id'],
            'type' => ['required', new Enum(TypeRegulation::class)],
            'regulations' => ['required', 'array'],
            'regulations.*.method' => ['required', 'exists:regulation_methods,id'],
            'regulations.*.amount' => ['required', new ValidateAmountForRegulateFactureRule($this->facture_id, $this->type)],
            'regulations.*.comment' => ['nullable', $requiredReason],
            'regulations.*.reason' => ['nullable', $requiredReason],
        ];
    }
}
