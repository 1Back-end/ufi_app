<?php

namespace App\Rules;

use App\Enums\StatusRegulation;
use App\Enums\TypeRegulation;
use App\Models\Facture;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Log;

class ValidateAmountForRegulateFactureRule implements ValidationRule
{
    private Facture $facture;
    public function __construct(
        public int $factureId,
        public int $type,
        public ?int $update = null,
    ) {
        $this->facture = Facture::find($this->factureId);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Le montant du règlement ne doit pas excéder le montant de la facture
        $amount = 0;
        switch ($this->type) {
            case TypeRegulation::CLIENT->value:
                $amount = $this->facture->amount_client;
                $amountRegulated = $this->facture->regulations()->count()
                    ? $this->facture
                    ->regulations()
                    ->where('regulations.particular', false)
                    ->where('regulations.state', StatusRegulation::ACTIVE->value)
                    ->when($this->update, function ($query) {
                        $query->whereNot('regulations.id', $this->update);
                    })
                    ->sum('amount')
                    : 0;
                $value = ($amountRegulated / 100) + $value;
                break;
            case TypeRegulation::ASSURANCE->value:
                $amount = $this->facture->amount_pc;
                break;
            case TypeRegulation::ASSOCIATE->value:
                $amount = $this->facture->amount_client;
                break;
        }

        if ($value > $amount) {
            $fail('Le montant du règlement ne doit pas excéder le montant de la facture');
        }
    }
}
