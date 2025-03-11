<?php

namespace Database\Factories;

use App\Models\Hopital;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class HopitalFactory extends Factory
{
    protected $model = Hopital::class;

    public function definition()
    {
        return [
            'code_hopi' => $this->faker->word(),
            'nom_hopi' => $this->faker->word(),
            'Abbreviation_hopi' => $this->faker->word(),
            'addresse_hopi' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'create_by_hopi' => User::factory(),
            'update_by_hopi' => User::factory(),
        ];
    }
}
