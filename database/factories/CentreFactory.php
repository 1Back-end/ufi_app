<?php

namespace Database\Factories;

use App\Models\Centre;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class CentreFactory extends Factory
{
    protected $model = Centre::class;

    public function definition()
    {
        return [
            'nom_centre' => $this->faker->word(),
            'tel_centre' => $this->faker->word(),
            'numero_contribuable_centre' => $this->faker->word(),
            'registre_com_centre' => $this->faker->word(),
            'fax_centre' => $this->faker->word(),
            'email_centre' => $this->faker->unique()->safeEmail(),
            'numero_autorisation_centre' => $this->faker->word(),
            'logo_centre' => $this->faker->word(),
            'date_creation_centre' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
