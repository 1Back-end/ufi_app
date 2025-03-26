<?php

namespace App\Models\Trait;

trait UpdatingUser
{
    public static function bootUpdatingUser(): void
    {
        $authId = auth()->id();
        static::updating(function ($model) use ($authId) {
            $model->updated_by = $authId;
        });
    }
}
