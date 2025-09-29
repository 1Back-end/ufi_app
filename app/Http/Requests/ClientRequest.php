<?php

namespace App\Http\Requests;

use App\Enums\StatusClient;
use App\Enums\TypeClient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Unique;

class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        //Todo: Ajouter une restriction en fonction de la table droit.
        return true;
    }

    public function rules(): array
    {
        if ($this->isMethod('PUT')) {
            $uniqueEmail = (new Unique('clients', 'email'))->ignore($this->route('client'));
        }
        else {
            $uniqueEmail = 'unique:clients,email';
        }

        return [
            'societe_id' => ['nullable', 'exists:societes,id'],
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
            'date_naiss_cli_estime' => [
                'boolean',
            ],
            'age' => ['nullable', 'required_if:date_naiss_cli_estime,true', 'integer', 'min:0', 'max:120'],
            'tel_cli' => ['required'],
            'tel2_cli' => ['nullable'],
            'type_cli' => ['required', new Enum(TypeClient::class)],
            'renseign_clini_cli' => ['nullable'],
            'assure_pa_cli' => ['boolean'],
            'afficher_ap' => ['boolean'],
            'nom_assure_principale_cli' => ['required_if:assure_pa_cli,false', 'nullable'],
            'document_number_cli' => ['nullable'],
            'nom_conjoint_cli' => ['nullable'],
            'prenom_conjoint_cli' => ['nullable'],
            'email' => ['nullable', 'email', $uniqueEmail],
            'client_anonyme_cli' => ['boolean'],
            'addresse_cli' => ['nullable'],
            'tel_whatsapp' => ['boolean'],
            'urgent_contact' => ['nullable'],
            'urgent_contact_number' => ['nullable'],
            'religion' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_naiss_cli.required_if' => 'La date de naissance est obligatoire.',
            'date_naiss_cli.before_or_equal' => 'La date de naissance doit être antérieure ou égale à la date actuelle.',
            'date_naiss_cli.after_or_equal' => 'La date de naissance doit être postérieure ou égale à 1900-01-01.',
            'age.required_if' => 'L’âge est obligatoire.',
            'age.integer' => 'L’âge doit être un nombre entier.',
            'age.min' => 'L’âge doit être au moins 0.',
            'age.max' => 'L’âge doit être au plus 120.',
            'status_familiale_id.required' => 'Le statut familial est obligatoire.',
            'type_document_id.required' => 'Le type de document est obligatoire.',
            'sexe_id.required' => 'Le sexe est obligatoire.',
            'nomcomplet_client.required' => 'Le nom complet est obligatoire.',
            'tel_cli.required' => 'Le numéro de téléphone est obligatoire.',
            'type_cli.required' => 'Le type de client est obligatoire.',
            'email.email' => 'L\'adresse email doit avoir un format valide.',
            'email.unique' => 'L\'adresse email doit être unique.',
        ];
    }
}
