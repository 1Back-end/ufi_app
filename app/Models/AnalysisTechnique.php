<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnalysisTechnique extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'duration',
        'detail',
    ];

    protected $appends = ['duration_detail'];

    protected function durationDetail(): Attribute
    {
        return Attribute::make(
            get: function() {
                $duration = '';
                // Transform duration in days, hours and minutes
                $days = floor($this->duration / 1440);
                $hours = floor(($this->duration - ($days * 1440)) / 60);
                $minutes = $this->duration - ($days * 1440) - ($hours * 60);
                if ($days > 0) {
                    $duration .= $days . ' jours ';
                }
                if ($hours > 0) {
                    $duration .= $hours . ' heures ';
                }
                if ($minutes > 0) {
                    $duration .= $minutes . ' minutes';
                }
                return $duration;
            },
        );
    }
}
