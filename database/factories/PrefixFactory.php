<?php

namespace Database\Factories;

use App\Models\Prefix;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PrefixFactory extends Factory
{
    protected $model = Prefix::class;

    public function definition(): array
    {
        return [
            'prefixe' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'created_by' => User::first(),
            'updated_by' => User::first(),
        ];
    }
}
