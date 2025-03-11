<?php

namespace Database\Factories;

use App\Models\Consultant;
use App\Models\Hopital;
use App\Models\Service_Hopital;
use App\Models\Specialite;
use App\Models\Titre;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ConsultantFactory extends Factory
{
    protected $model = Consultant::class;

    public function definition()
    {
        return [
            'ref_consult' => $this->faker->word(),
            'nom_consult' => $this->faker->word(),
            'prenom_consult' => $this->faker->word(),
            'nomcomplet_consult' => $this->faker->word(),
            'tel_consult' => $this->faker->word(),
            'tel1_consult' => $this->faker->word(),
            'email_consul' => $this->faker->unique()->safeEmail(),
            'type_consult' => $this->faker->word(),
            'status_consult' => $this->faker->word(),
            'TelWhatsApp' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'user_id' => User::factory(),
            'code_hopi' => Hopital::factory(),
            'code_service_hopi' => Service_Hopital::factory(),
            'code_specialite' => Specialite::factory(),
            'code_titre' => Titre::factory(),
            'create_by_consult' => User::factory(),
            'update_by_consult' => User::factory(),
        ];
    }
}
