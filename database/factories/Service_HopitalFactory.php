<?php

namespace Database\Factories;

use App\Models\Service_Hopital;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class Service_HopitalFactory extends Factory
{
    protected $model = Service_Hopital::class;

    public function definition()
    {
        return [
            'code_service_hopi' => $this->faker->word(),
            'nom_service_hopi' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'create_by_service_hopi' => User::factory(),
            'update_by_service_hopi' => User::factory(),
        ];
    }
}
