<?php

namespace Database\Factories;

use App\Models\Acte;
use App\Models\TypeActe;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ActeFactory extends Factory
{
    protected $model = Acte::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'pu' => $this->faker->randomNumber(),
            'delay' => $this->faker->randomNumber(),
            'state' => $this->faker->boolean(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'created_by' => User::factory(),
            'updated_by' => User::factory(),
            'type_acte_id' => TypeActe::factory(),
        ];
    }
}
