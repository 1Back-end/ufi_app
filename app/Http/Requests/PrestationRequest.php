<?php

namespace App\Http\Requests;

use App\Enums\TypePrestation;
use App\Enums\TypeSalle;
use App\Models\Acte;
use App\Models\Consultation;
use App\Models\Prestation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Enum;
use Mockery\Matcher\Type;
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
            // Actes
            'actes' => ['nullable', 'array', 'required_if:type,' . TypePrestation::ACTES->value],
            'actes.*.id' => ['integer', 'required_if:type,' . TypePrestation::ACTES->value, 'exists:actes,id'],
            'actes.*.remise' => ['min:0', 'numeric', 'min:0', 'max:100'],
            'actes.*.quantity' => ['integer', 'required_if:type,' . TypePrestation::ACTES->value, 'min:1'],
            'actes.*.date_rdv' => ['required_if:type,' . TypePrestation::ACTES->value,],
            // Soins
            'soins' => ['nullable', 'array', 'required_if:type,' . TypePrestation::SOINS->value,],
            'soins.*.id' => ['integer', 'required_if:type,' . TypePrestation::SOINS->value, 'exists:soins,id'],
            'soins.*.remise' => ['min:0', 'numeric', 'max:100'],
            'soins.*.nbr_days' => ['integer', 'required_if:type,' . TypePrestation::SOINS->value, 'min:1'],
            'soins.*.type_salle' => ['required_if:type,' . TypePrestation::SOINS->value, new Enum(TypeSalle::class)],
            'soins.*.honoraire' => ['integer', 'required_if:type,' . TypePrestation::SOINS->value,],
            // Consultations
            'consultations' => ['nullable', 'array', 'required_if:type,' . TypePrestation::CONSULTATIONS->value],
            'consultations.*.id' => ['integer', 'required_if:type,' . TypePrestation::CONSULTATIONS->value, 'exists:consultations,id'],
            'consultations.*.remise' => ['min:0', 'numeric', 'max:100'],
            'consultations.*.quantity' => ['integer', 'required_if:type,' . TypePrestation::CONSULTATIONS->value, 'min:1'],
            'consultations.*.date_rdv' => ['required_if:type,' . TypePrestation::CONSULTATIONS->value, 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'actes.*.date_rdv.required_if' => __("La date de rendez-vous est requise !"),
            'soins.*.date_rdv.required_if' => __("La date de rendez-vous est requise !"),
            'consultations.*.date_rdv.required_if' => __("La date de rendez-vous est requise !"),
            'soins.*.type_salle.required_if' => __("Le type de salle est requis !"),
            'soins.*.honoraire.required_if' => __("L'honoraire est requis !"),
            'soins.*.nbr_days.required_if' => __("Le nombre de jours est requis !"),
            'actes.*.quantity.required_if' => __("La quantité est requise !"),
            'consultations.*.quantity.required_if' => __("La quantité est requise !"),
            'actes.*.remise.required_if' => __("La remise est requise !"),
            'soins.*.remise.required_if' => __("La remise est requise !"),
            'consultations.*.remise.required_if' => __("La remise est requise !"),
            'actes.*.id.required_if' => __("L'acte est requis !"),
            'soins.*.id.required_if' => __("Le soin est requis !"),
            'consultations.*.id.required_if' => __("La consultation est requise !"),
            'actes.*.id.exists' => __("L'acte n'existe pas !"),
            'soins.*.id.exists' => __("Le soin n'existe pas !"),
            'consultations.*.id.exists' => __("La consultation n'existe pas !"),
            'payable_by.exists' => __("Le client n'existe pas !"),
            'client_id.exists' => __("Le client n'existe pas !"),
            'consultant_id.exists' => __("Le consultant n'existe pas !"),
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

                // Log::info($prestations);

                $errorConflit = [];

                foreach ($prestations as $prestation) {
                    foreach ($this->actes as $item) {
                        $acte = Acte::find($item['id']);
                        $date_rdv = Carbon::createFromTimeString($item['date_rdv']);
                        $acteSearch = $prestation->actes()
                            ->wherePivot('date_rdv', '<=', $date_rdv)
                            ->wherePivot('date_rdv_end', '>=', $date_rdv)
                            ->first();


                        if ($acteSearch) {
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
            case TypePrestation::SOINS->value:
                break;
            case TypePrestation::CONSULTATIONS->value:

                $prestations = Prestation::whereRegulated(0)
                    ->where(function (Builder $query) {
                        $query->where('consultant_id', $this->consultant_id)
                            ->orWhere('client_id', $this->client_id);
                    })
                    ->when($prestationId, function (Builder $query) use ($prestationId) {
                        $query->where('id', '!=', $prestationId);
                    })
                    ->get();

                $errorConflit = [];

                foreach ($prestations as $prestation) {
                    foreach ($this->consultations as $item) {
                        $consultation = Consultation::find($item['id']);
                        $date_rdv = Carbon::createFromTimeString($item['date_rdv']);
                        $consultationSearch = $prestation->consultations()
                            ->wherePivot('date_rdv', '<=', $date_rdv)
                            ->wherePivot('date_rdv_end', '>=', $date_rdv)
                            ->first();


                        if ($consultationSearch) {
                            $errorConflit = [
                                'message' => __("Ce consultant ou Ce client a déjà un rendez-vous à cette période du {$consultationSearch->pivot->date_rdv} au {$consultationSearch->pivot->date_rdv_end}"),
                                'actes' => $consultationSearch
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
