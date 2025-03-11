<?php

namespace Database\Factories;

use App\Models\Specialite;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class SpecialiteFactory extends Factory
{
    protected $model = Specialite::class;

    public function definition()
    {
        return [
            'code_specialite' => $this->faker->word(),
            'nom_specialite' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'create_by_specialite' => User::factory(),
            'update_by_specialite' => User::factory(),
        ];
    }
}
