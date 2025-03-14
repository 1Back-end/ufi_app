<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        return [
            'nom_utilisateur' => $this->faker->word(),
            'password' => $this->faker->word(),
            'date_expiration_mot_passe' => now()->addDays(30),
            'email' => $this->faker->unique()->safeEmail(),
            'status_utilisateur' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'profile_id' => Profile::factory(),
        ];
    }
}
