<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Profile;
use App\Models\Droit;

class ProfileDroitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $profiles = Profile::all();
        $droits = Droit::all();

        foreach ($profiles as $profile) {
            $profile->droits()->attach(
                $droits->random(2), ['date_creation_profile_droit' => now()]
            );
        }
    }
}
