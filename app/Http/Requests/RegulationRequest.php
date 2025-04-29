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
            'regulation_method_id' => ['required', 'exists:regulation_methods'],
            'facture_id' => ['required', 'exists:factures'],
            'type' => ['required', new Enum(TypeRegulation::class)],
            'amount' => ['required', 'integer', new ValidateAmountForRegulateFactureRule($this->facture_id, $this->type)],
            'comment' => ['nullable', $requiredReason],
            'reason' => ['nullable', $requiredReason],
        ];
    }
}
