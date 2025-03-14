<?php

namespace Database\Factories;

use App\Models\Societe;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class SocieteFactory extends Factory
{
    protected $model = Societe::class;

    public function definition(): array
    {
        return [
            'nom_soc_cli' => $this->faker->unique()->word(),
            'tel_soc_cli' => $this->faker->word(),
            'Adress_soc_cli' => $this->faker->word(),
            'num_contrib_soc_cli' => $this->faker->word(),
            'email_soc_cli' => $this->faker->unique()->safeEmail(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'create_by' => User::first(),
            'updated_by' => User::first(),
        ];
    }
}
