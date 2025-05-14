<?php

namespace Database\Factories;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UniteProduit>
 */
class UniteProduitFactory extends Factory
{
    protected $model = \App\Models\UniteProduit::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word,
            'code' => $this->faker->optional()->word,
            'created_by' => User::factory(), // Assurez-vous qu'un utilisateur existe pour la clé étrangère
            'updated_by' => User::factory(),
            'is_deleted' => $this->faker->boolean,
        ];
    }
}
