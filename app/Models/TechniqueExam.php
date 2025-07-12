<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechniqueExam extends Model
{
    protected $fillable = [
        'analysis_technique_id',
        'type',
        'examen_id',
    ];

    public function analysisTechnique(): BelongsTo
    {
        return $this->belongsTo(AnalysisTechnique::class, 'analysis_technique_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class, 'examen_id');
    }
}
