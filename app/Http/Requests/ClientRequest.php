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
        $date_naissance = $this->input('date_naiss_cli');
        $age = $date_naissance ? Carbon::parse($date_naissance)->age : null;

        return [
            'user_id' => ['required', 'exists:users'],
            'societe_id' => ['required', 'exists:societes'],
            'prefix_id' => ['required', 'exists:prefixes'],
            'status_familiale_id' => ['required', 'exists:status_familiales'],
            'type_document_id' => ['required', 'exists:type_documents'],
            'sexe_id' => ['required', 'exists:sexes'],
//            'nomcomplet_client' => ['required'], // Todo: ne doit être un getter et non être enregistrer en BD
            'prenom_cli' => ['nullable'],
            'nom_cli' => ['required'],
            'secondprenom_cli' => ['nullable'],
            'date_naiss_cli' => ['nullable',
                'date',
                'date_format:Y-m-d',
                'before_or_equal:' . now()->format('Y-m-d'),
                'after_or_equal:1900-01-01',
            ],
            'enfant_cli' => [
                'boolean',
                function ($attribute, $value, $fail) use ($age) {
                    if ($age !== null && $age < 14 && $value) {
                        $fail("L'option '$attribute' doit être false pour les moins de 14 ans.");
                    }
                },
            ],
//            'ref_cli' => ['required'],
            'tel_cli' => ['required'],
            'tel2_cli' => ['nullable'],
            'type_cli' => ['required', new Enum(TypeClient::class)],
            'renseign_clini_cli' => ['nullable'],
            'assure_pa_cli' => ['boolean'],
            'afficher_ap' => ['boolean'],
            'nom_assure_principale_cli' => ['required_if:assure_pa_cli,false', 'nullable', 'exists:clients,nom_cli'], // Todo: Attente de validation
            'document_number_cli' => ['nullable'],
            'nom_conjoint_cli' => ['nullable'],
            'email_cli' => ['nullable'],
            'date_naiss_cli_estime' => [
                'boolean',
                function ($attribute, $value, $fail) {
                    $hasBirthdate = !empty($this->input('date_naiss_cli'));
                    $hasEstimatedAge = !empty($age);

                    if ($hasBirthdate && $value !== true) {
                        $fail("L'option '$attribute' doit être 'Oui' lorsque la date de naissance est connue.");
                    }

                    if ($hasEstimatedAge && $value !== false) {
                        $fail("L'option '$attribute' doit être 'Non' lorsque seule l'âge est estimé.");
                    }
                },
            ],
            'status_cli' => ['boolean', new Enum(StatusClient::class)],
            'client_anonyme_cli' => ['boolean'],
            'addresse_cli' => ['nullable'],
            'create_by_cli' => ['required', 'exists:users'],
            'update_by_cli' => ['nullable', 'exists:users'],
            'tel_whatsapp' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [

        ];
    }
}
