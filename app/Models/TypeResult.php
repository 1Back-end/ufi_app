<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TypeResult extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'accept_saisi_user',
        'afficher_result',
        'type',
        'cat_predefined_list_id',
    ];

    protected $with = [
        'catPredefinedList',
        'catPredefinedList.predefinedLists',
    ];

    protected function casts(): array
    {
        return [
            'accept_saisi_user' => 'boolean',
            'afficher_result' => 'boolean',
        ];
    }

    public function catPredefinedList(): BelongsTo
    {
        return $this->belongsTo(CatPredefinedList::class, 'cat_predefined_list_id');
    }
}
