<?php

namespace Database\Factories;

use App\Models\StatusFamiliale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class StatusFamilialeFactory extends Factory
{
    protected $model = StatusFamiliale::class;

    public function definition(): array
    {
        return [
            'description_statusfam' => $this->faker->text(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'created_by' => User::first(),
            'updated_by' => User::first(),
        ];
    }
}
