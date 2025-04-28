<?php

namespace App\Http\Requests;

use App\Enums\TypePrestation;
use App\Models\Acte;
use App\Models\Prestation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Enum;
use PHPUnit\Framework\Exception;
use Symfony\Component\HttpFoundation\Response;

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
            'actes.*.id' => ['integer', 'required_if:type,' . TypePrestation::ACTES->value],
            'actes.*.remise' => ['min:0', 'integer'],
            'actes.*.quantity' => ['integer', 'required_if:type,' . TypePrestation::ACTES->value, 'min:1'],
            'actes.*.date_rdv' => ['required_if:type,' . TypePrestation::ACTES->value,]
        ];
    }

    /**
     * @return array|void
     */
    public function validateRdvDate(?int $prestationId = null)
    {
        switch ($this->type) {
            case TypePrestation::ACTES->value:
                $prestations = Prestation::whereRegulated(0)
                    ->where(function (Builder $query) {
                        $query->where('consultant_id', $this->consultant_id)
                            ->orWhere('client_id', $this->client_id);
                    })
                    ->when($prestationId, function (Builder $query) use ($prestationId) {
                        $query->where('id', '!=', $prestationId);
                    })
                    ->get();

                Log::info($prestations);

                $errorConflit = [];

                foreach ($prestations as $prestation) {
                    foreach ($this->actes as $item) {
                        $acte = Acte::find($item['id']);
                        $date_rdv = Carbon::createFromTimeString($item['date_rdv']);
                        $acteSearch = $prestation->actes()
                            ->wherePivot('date_rdv', '<=', $date_rdv)
                            ->wherePivot('date_rdv_end', '>=', $date_rdv)
                            ->first();


                        if($acteSearch) {
                            $errorConflit = [
                                'message' => __("Ce consultant ou Ce client a déjà un rendez-vous à cette période du {$acteSearch->pivot->date_rdv} au {$acteSearch->pivot->date_rdv_end}"),
                                'actes' => $acteSearch
                            ];
                            break;
                        }
                    }

                    if ($errorConflit) break;
                }

                if ($errorConflit) {
                    return $errorConflit;
                }

                break;
            default:
                throw new Exception("Ce type de prestation n'est pas encore implémenté", Response::HTTP_BAD_REQUEST);
        }
    }
}
