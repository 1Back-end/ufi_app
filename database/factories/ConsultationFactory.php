<?php

namespace Database\Factories;
use App\Models\Consultation;
use App\Models\Typeconsultation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Consultation>
 */
class ConsultationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Consultation::class;

    public function definition()
    {
        return [
            'typeconsultation_id' => Typeconsultation::factory(),
            'name' => $this->faker->randomElement(['Consultation générale', 'Pédiatrie', 'Gynécologie', 'Urgence']),
            'pu' => $this->faker->numberBetween(5000, 20000),
            'validation_date' => now()->timestamp,
            'status' => $this->faker->randomElement(['Actif', 'Inactif']),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
            'is_deleted' => false,
        ];
    }
}
