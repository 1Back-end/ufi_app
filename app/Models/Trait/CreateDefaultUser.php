<?php

namespace App\Models\Trait;

use App\Models\User;
use App\Notifications\DefaultUserCreated;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

trait CreateDefaultUser
{
    public static function bootCreateDefaultUser(): void
    {
        static::creating(function ($model) {
            $password = Str::password(8);
            $username = Str::random(8);

            // Création d’un user par défaut
            $user = User::create([
                'login' => $username,
                'email' => $model->email,
                'nom_utilisateur' => $username,
                'password' => Hash::make($password),
                'default' => true
            ]);

            $model->user_id = $user->id;

            // Link user to centre
            if (request()->header('centre')) {
                $user->centres()->attach(request()->header('centre'), ['default' => true]);
            }

            // Envoi d'une notification à l'utilisateur avec les logins et mot de passe par défaut
            $user->notify(new DefaultUserCreated($username, $password));
        });
    }
}
