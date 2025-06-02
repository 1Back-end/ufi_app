<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

class AddDefaultParametersSeeder extends Seeder
{
    public function run(): void
    {
        if (! Setting::where('key','day_validity_fidelity_card')->first()) {
            Setting::create( [
                'key' => "day_validity_fidelity_card",
                'description' => "Définit le  nombre de jour de validité d'une carte de fidélité exprimé en jours.",
                'value' => 30,
                'created_by' => User::first()->id,
                'updated_by' => User::first()->id,
            ]);
        }

        if (! Setting::where('key','rdv_duration')->first()) {
            Setting::create( [
                'key' => "rdv_duration",
                'description' => "Définit le nombre temps qu'un rendez-vous met exprimé en minute.",
                'value' => 120,
                'created_by' => User::first()->id,
                'updated_by' => User::first()->id,
            ]);
        }

        if (! Setting::where('key','rdv_validity_by_day')->first()) {
            Setting::create( [
                'key' => "rdv_validity_by_day",
                'description' => "Définit le nombre nombre de jour d'un rendez-vous.",
                'value' => 120,
                'created_by' => User::first()->id,
                'updated_by' => User::first()->id,
            ]);
        }
    }
}
