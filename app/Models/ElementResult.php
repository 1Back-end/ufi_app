<?php

namespace App\Models;

use App\Models\Trait\UpdatingUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ElementResult extends Model
{
    use SoftDeletes, UpdatingUser;

    protected $fillable = [
        'code',
        'name',
        'category_element_result_id',
        'created_by',
        'updated_by',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function categoryElementResult(): BelongsTo
    {
        return $this->belongsTo(CategoryElementResult::class);
    }
}
