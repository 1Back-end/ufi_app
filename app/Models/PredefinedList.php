<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PredefinedList extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'cat_predefined_list_id',
    ];

    public function catPredefinedList(): BelongsTo
    {
        return $this->belongsTo(CatPredefinedList::class, 'cat_predefined_list_id');
    }
}
