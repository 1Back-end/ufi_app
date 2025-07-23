<?php

namespace App\Pivots;

use App\Enums\StateExamen;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphPivot;


class PrelevementsPivot extends MorphPivot
{
    protected $casts = [
        'prelevements' => 'array',
        'status_examen' => StateExamen::class,
    ];

    protected $appends = ['status_examen_label', 'latest_prelevement'];

    protected function statusExamenLabel(): Attribute
    {
        return Attribute::make(
            get: fn($value, array $attributes) => StateExamen::label($this->status_examen),
        );
    }

    protected function latestPrelevement(): Attribute
    {
        return Attribute::make(
            get: fn($value, array $attributes) => collect($this->prelevements)->where('cancel', false)->last(),
        );
    }
}
