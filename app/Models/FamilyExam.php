<?php

namespace App\Models;

use App\Models\Trait\UpdatingUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class FamilyExam extends Model
{
    use SoftDeletes, UpdatingUser;

    protected $fillable = [
        'code',
        'name',
        'description',
        'created_by',
        'updated_by',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo('App\Models\User', 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo('App\Models\User', 'updated_by');
    }

    public function subFamilyExam(): HasOne
    {
        return $this->hasOne(SubFamilyExam::class, 'family_exam_id');
    }
}
