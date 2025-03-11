<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;


class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $profile = Profile::first(); // Utilise le premier profil existant ou crée-en un si nécessaire

            User::insert([
                [
                    'profile_id' => $profile->id,
                    'nom_utilisateur' => 'admin',
                    'mot_de_passe' => Hash::make('password'),
                    'date_expiration_mot_passe' => now()->addMonths(3),
                    'email_utilisateur' => 'admin@example.com',
                    'status_utilisateur' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'profile_id' => $profile->id,
                    'nom_utilisateur' => 'utilisateur1',
                    'mot_de_passe' => Hash::make('password'),
                    'date_expiration_mot_passe' => now()->addMonths(3),
                    'email_utilisateur' => 'user1@example.com',
                    'status_utilisateur' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'profile_id' => $profile->id,
                    'nom_utilisateur' => 'utilisateur2',
                    'mot_de_passe' => Hash::make('password'),
                    'date_expiration_mot_passe' => now()->addMonths(3),
                    'email_utilisateur' => 'user2@example.com',
                    'status_utilisateur' => 'inactif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'profile_id' => $profile->id,
                    'nom_utilisateur' => 'utilisateur3',
                    'mot_de_passe' => Hash::make('password'),
                    'date_expiration_mot_passe' => now()->addMonths(3),
                    'email_utilisateur' => 'user3@example.com',
                    'status_utilisateur' => 'actif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'profile_id' => $profile->id,
                    'nom_utilisateur' => 'utilisateur4',
                    'mot_de_passe' => Hash::make('password'),
                    'date_expiration_mot_passe' => now()->addMonths(3),
                    'email_utilisateur' => 'user4@example.com',
                    'status_utilisateur' => 'suspendu',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

    }
}
