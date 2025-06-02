<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

class AddDefaultParametersSeeder extends Seeder
{
    public function run(): void
    {
        if (! Setting::whereKey('day_validity_fidelity_card')->exists()) {
            Setting::create( [
                'key' => "day_validity_fidelity_card",
                'description' => "Détermine le  nombre de jour de validité d'une carte de fidélité exprimé en jours.",
                'value' => 30,
                'created_by' => User::first()->id,
                'updated_by' => User::first()->id,
            ]);
        }

        if (! Setting::whereKey('rdv_duration')->exists()) {
            Setting::create( [
                'key' => "rdv_duration",
                'description' => "Détermine le nombre temps qu'un rendez-vous met exprimé en minute.",
                'value' => 120,
                'created_by' => User::first()->id,
                'updated_by' => User::first()->id,
            ]);
        }
    }
}
