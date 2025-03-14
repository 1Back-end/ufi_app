<?php

namespace App\Http\Requests;

use App\Enums\StatusClient;
use App\Enums\TypeClient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rules\Enum;

class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        //Todo: Ajouter une restriction en fonction de la table droit.
        return true;
    }

    public function rules(): array
    {
        return [
            'site_id' => ['required', 'exists:centres,id'],
            'societe_id' => ['required', 'exists:societes,id'],
            'prefix_id' => ['required', 'exists:prefixes,id'],
            'status_familiale_id' => ['required', 'exists:status_familiales,id'],
            'type_document_id' => ['required', 'exists:type_documents,id'],
            'sexe_id' => ['required', 'exists:sexes,id'],
            'nomcomplet_client' => ['required'],
            'prenom_cli' => ['nullable'],
            'nom_cli' => ['required'],
            'secondprenom_cli' => ['nullable'],
            'date_naiss_cli' => ['nullable',
                'required_if:date_naiss_cli_estime,false',
                'date:Y-m-d',
                'before_or_equal:' . now()->format('Y-m-d'),
                'after_or_equal:1900-01-01',
            ],
            'tel_cli' => ['required'],
            'tel2_cli' => ['nullable'],
            'type_cli' => ['required', new Enum(TypeClient::class)],
            'renseign_clini_cli' => ['nullable'],
            'assure_pa_cli' => ['boolean'],
            'afficher_ap' => ['boolean'],
            'nom_assure_principale_cli' => ['required_if:assure_pa_cli,false', 'nullable', 'exists:clients,nomcomplet_client'],
            'document_number_cli' => ['nullable'],
            'nom_conjoint_cli' => ['nullable'],
            'email_cli' => ['nullable', 'email'],
            'date_naiss_cli_estime' => [
                'boolean',
//                function ($attribute, $value, $fail) {
//                    $hasBirthdate = !empty($this->input('date_naiss_cli'));
//                    $hasEstimatedAge = !empty($age);
//
//                    if ($hasBirthdate && $value !== true) {
//                        $fail("L'option '$attribute' doit être 'Oui' lorsque la date de naissance est connue.");
//                    }
//
//                    if ($hasEstimatedAge && $value !== false) {
//                        $fail("L'option '$attribute' doit être 'Non' lorsque seule l'âge est estimé.");
//                    }
//                },
            ],
            'age' => ['nullable', 'required_if:date_naiss_cli_estime,true', 'integer', 'min:0', 'max:120'],
//            'status_cli' => ['boolean', new Enum(StatusClient::class)],
            'client_anonyme_cli' => ['boolean'],
            'addresse_cli' => ['nullable'],
            'tel_whatsapp' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [

        ];
    }
}
