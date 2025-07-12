<?php

namespace App\Pivots;

use Illuminate\Database\Eloquent\Relations\MorphPivot;


class PrelevementsPivot extends MorphPivot
{
    protected $casts = [
        'prelevements' => 'array',
    ];
}
