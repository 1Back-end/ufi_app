<?php

namespace Database\Factories;
use App\Models\User;
use App\Models\GroupProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = \App\Models\Category::class;

    public function definition()
    {
        return [
            'name' => $this->faker->unique()->word(),
            'group_product_id' => GroupProduct::factory(), // Génère automatiquement un groupe associé
            'description' => $this->faker->sentence(),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
            'is_deleted' => $this->faker->boolean(10), // 10% chance d'être true
        ];
    }
}
