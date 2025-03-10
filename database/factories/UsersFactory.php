<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class UsersFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        return [
            'nom_utilisateur' => $this->faker->word(),
            'mot_de_passe' => $this->faker->word(),
            'date_expiration_mot_passe' => $this->faker->word(),
            'email_utilisateur' => $this->faker->unique()->safeEmail(),
            'status_utilisateur' => $this->faker->word(),
            'date_creation_utilisateur' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'profile_id' => Profile::factory(),
        ];
    }
}
