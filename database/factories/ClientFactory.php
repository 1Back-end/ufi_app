<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Prefix;
use App\Models\Sexe;
use App\Models\Societe;
use App\Models\StatusFamiliale;
use App\Models\TypeDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition()
    {
        return [
            'nomcomplet_client' => $this->faker->word(),
            'prenom_cli' => $this->faker->word(),
            'nom_cli' => $this->faker->word(),
            'secondprenom_cli' => $this->faker->word(),
            'date_naiss_cli' => Carbon::now(),
            'enfant_cli' => $this->faker->boolean(),
            'ref_cli' => $this->faker->word(),
            'tel_cli' => $this->faker->word(),
            'tel2_cli' => $this->faker->word(),
            'type_cli' => $this->faker->word(),
            'renseign_clini_cli' => $this->faker->word(),
            'assure_pa_cli' => $this->faker->boolean(),
            'afficher_ap' => $this->faker->boolean(),
            'nom_assure_principale_cli' => $this->faker->word(),
            'document_number_cli' => $this->faker->word(),
            'nom_conjoint_cli' => $this->faker->word(),
            'email' => $this->faker->unique()->safeEmail(),
            'date_naiss_cli_estime' => $this->faker->boolean(),
            'status_cli' => $this->faker->boolean(),
            'client_anonyme_cli' => $this->faker->boolean(),
            'addresse_cli' => $this->faker->word(),
            'tel_whatsapp' => $this->faker->boolean(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'user_id' => User::factory(),
            'societe_id' => Societe::first(),
            'prefix_id' => Prefix::first(),
            'status_familiale_id' => StatusFamiliale::first(),
            'type_document_id' => TypeDocument::first(),
            'sexe_id' => Sexe::first(),
            'created_by' => User::first(),
            'updated_by' => User::first(),
        ];
    }
}
