<?php

namespace Database\Factories;

use App\Models\TypeActe;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class TypeActeFactory extends Factory
{
    protected $model = TypeActe::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'k_modulateur' => $this->faker->randomNumber(),
            'state' => $this->faker->word(),
            'cotation' => $this->faker->randomNumber(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
}
