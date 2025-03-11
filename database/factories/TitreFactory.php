<?php

namespace Database\Factories;

use App\Models\Titre;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class TitreFactory extends Factory
{
    protected $model = Titre::class;

    public function definition()
    {
        return [
            'code_titre' => $this->faker->word(),
            'nom_titre' => $this->faker->word(),
            'abbreviation_titre' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'create_by' => User::factory(),
            'update_by' => User::factory(),
        ];
    }
}
