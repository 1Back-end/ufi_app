<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatPredefinedList extends Model
{
    protected $fillable = [
        'name',
        'slug',
    ];

    public function predefinedLists(): HasMany|CatPredefinedList
    {
        return $this->hasMany(PredefinedList::class, 'cat_predefined_list_id');
    }
}
