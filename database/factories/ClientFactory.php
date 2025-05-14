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
            'nomcomplet_client' => $this->faker->name(),
            'prenom_cli' => $this->faker->firstName(),
            'nom_cli' => $this->faker->lastName(),
            'secondprenom_cli' => $this->faker->firstName(),
            'date_naiss_cli' => $this->faker->date('Y-m-d', '-18 years'),
            'enfant_cli' => $this->faker->boolean(),
            'ref_cli' => strtoupper($this->faker->bothify('REF-####')),
            'tel_cli' => $this->faker->phoneNumber(),
            'tel2_cli' => $this->faker->optional()->phoneNumber(),
            'type_cli' => $this->faker->randomElement(['adulte', 'enfant', 'senior']),
            'renseign_clini_cli' => $this->faker->sentence(),
            'assure_pa_cli' => $this->faker->boolean(),
            'afficher_ap' => $this->faker->boolean(),
            'nom_assure_principale_cli' => $this->faker->name(),
            'document_number_cli' => strtoupper($this->faker->bothify('ID#######')),
            'nom_conjoint_cli' => $this->faker->optional()->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'date_naiss_cli_estime' => $this->faker->boolean(),
            'status_cli' => $this->faker->boolean(),
            'client_anonyme_cli' => $this->faker->boolean(),
            'addresse_cli' => $this->faker->address(),
            'tel_whatsapp' => $this->faker->boolean(),

            'created_at' => now(),
            'updated_at' => now(),

            'user_id' => User::factory(),
            'societe_id' => Societe::inRandomOrder()->first()?->id,
            'prefix_id' => Prefix::inRandomOrder()->first()?->id,
            'status_familiale_id' => StatusFamiliale::inRandomOrder()->first()?->id,
            'type_document_id' => TypeDocument::inRandomOrder()->first()?->id,
            'sexe_id' => Sexe::inRandomOrder()->first()?->id,
            'created_by' => User::inRandomOrder()->first()?->id,
            'updated_by' => User::inRandomOrder()->first()?->id,
        ];
    }

}
