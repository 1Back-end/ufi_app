<?php

namespace App\Models;

use App\Models\Trait\UpdatingUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TechniqueExam extends Model
{
    use SoftDeletes, UpdatingUser;

    protected $fillable = [
        'code',
        'analysis_technique_id',
        'type',
        'created_by',
        'updated_by',
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
}
