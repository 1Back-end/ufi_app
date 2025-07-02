<?php

namespace App\Models;

use App\Models\Trait\UpdatingUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubFamilyExam extends Model
{
    use SoftDeletes, UpdatingUser;

    protected $fillable = [
        'code',
        'name',
        'description',
        'family_exam_id',
        'created_by',
        'updated_by',
    ];

    public function familyExam(): BelongsTo
    {
        return $this->belongsTo(FamilyExam::class, 'family_exam_id');
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
