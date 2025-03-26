<?php

namespace App\Models\Trait;

use App\Models\Permission;
use App\Models\User;

trait UpdatingUser
{
    public static function bootUpdatingUser(): void
    {
        $authId = auth()->id();
        static::updating(function ($model) use ($authId) {
            $model->updated_by = $authId;
        });

        static::creating(function ($model) use ($authId) {

            if (is_a($model, Permission::class)) {
                $authId = User::whereLogin('SYSTEM')->first()->id;
            }
            $model->created_by = $authId;
            $model->updated_by = $authId;
        });
    }
}
