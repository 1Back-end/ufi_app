<?php

namespace Database\Factories;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GroupProduct>
 */
class GroupProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = \App\Models\GroupProduct::class;

    public function definition()
    {
        return [
            'name' => $this->faker->unique()->word,
            'created_by' => User::factory(), // Assurez-vous qu'un utilisateur existe pour la clé étrangère
            'updated_by' => User::factory(),
            'is_deleted' => $this->faker->boolean,
        ];
    }
}
