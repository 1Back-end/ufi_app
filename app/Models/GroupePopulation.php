<?php

namespace App\Models;

use App\Models\Trait\UpdatingUser;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupePopulation extends Model
{
    use UpdatingUser;

    protected $fillable = [
        'code',
        'name',
        'agemin',
        'agemax',
        'sex_id',
        'created_by',
        'updated_by',
    ];

    protected $appends = ['parse_age_min', 'parse_age_max'];

    protected function parseAgeMin(): Attribute
    {
        return Attribute::make(
            get: function ($value, array $attributes) {
                $years = floor($attributes['agemin'] / 12);
                $months = $attributes['agemin'] - $years * 12;

                if ($years > 0 && $months > 0) return "$years ans $months mois";
                if ($years > 0) return "$years ans";
                if ($months > 0) return "$months mois";
            },
        );
    }

    protected function parseAgeMax(): Attribute
    {
        return Attribute::make(
            get: function ($value, array $attributes) {
                if (!$attributes['agemax']) return "";

                $years = floor($attributes['agemax'] / 12);
                $months = $attributes['agemax'] - $years * 12;

                if ($years > 0 && $months > 0) return "$years ans $months mois";
                if ($years > 0) return "$years ans";
                if ($months > 0) return "$months mois";
            },
        );
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function sex(): BelongsTo
    {
        return $this->belongsTo(Sexe::class, 'sex_id');
    }
}
