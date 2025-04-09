<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Consultant;
use App\Models\Prestation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PrestationFactory extends Factory
{
    protected $model = Prestation::class;

    public function definition(): array
    {
        return [
            'prise_charge_id' => $this->faker->randomNumber(),
            'assureur' => $this->faker->randomNumber(),
            'payable' => $this->faker->boolean(),
            'programmation_date' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'client_id' => Client::factory(),
            'consultant_id' => Consultant::factory(),
            'payable_by' => Client::factory(),
        ];
    }
}
