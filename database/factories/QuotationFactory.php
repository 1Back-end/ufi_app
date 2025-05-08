<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Quotation>
 */
class QuotationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $now = Carbon::now(); // date et heure actuelles
        $code = 'Q' . $now->format('ymdHis'); // Q25040815223512

        return [
            'code' => $code,
            'taux' => $this->faker->numberBetween(5, 50),
            'description' => $this->faker->optional()->sentence(),
            'is_deleted' => false,
        ];
    }
}
