<?php

namespace Database\Factories;

use App\Models\Sexe;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class SexeFactory extends Factory
{
    protected $model = Sexe::class;

    public function definition(): array
    {
        return [
            'description_sex' => $this->faker->text(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'create_by' => User::first(),
            'update_by' => User::first(),
        ];
    }
}
