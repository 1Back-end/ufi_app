<?php

namespace Database\Factories;

use App\Models\TypeDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class TypeDocumentFactory extends Factory
{
    protected $model = TypeDocument::class;

    public function definition(): array
    {
        return [
            'description_typedoc' => $this->faker->text(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'created_by' => User::first(),
            'updated_by' => User::first(),
        ];
    }
}
