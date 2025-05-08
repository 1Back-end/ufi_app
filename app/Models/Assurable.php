<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Assurable extends Model
{

    use HasFactory;

    protected $fillable = [
        'assureur_id',
        'assurable_type',
        'assurable_id',
        'k_modulateur',
        'b',
        'pu',
    ];

    public function assureur()
    {
        return $this->belongsTo(Assureur::class);
    }

    public function assurable()
    {
        return $this->morphTo();
    }
}
