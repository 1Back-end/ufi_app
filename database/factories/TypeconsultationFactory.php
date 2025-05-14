<?php

namespace Database\Factories;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Typeconsultation>
 */
class TypeconsultationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = \App\Models\Typeconsultation::class;

    public function definition()
    {
        return [
            'name' => $this->faker->unique()->word(),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
            'is_deleted' => false,
        ];
    }
}
