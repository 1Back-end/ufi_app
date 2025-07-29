<?php

namespace App\Models;

use App\Models\Trait\UpdatingUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ElementPaillasse extends Model
{
    use UpdatingUser;

    protected $fillable = [
        'name',
        'unit',
        'numero_order',
        'cat_predefined_list_id',
        'element_paillasses_id',
        'type_result_id',
        'examen_id',
        'indent',
        'created_by',
        'updated_by'
    ];

    protected $with = [
        'catPredefinedList',
        'catPredefinedList.predefinedLists'
    ];

    public function typeResult(): BelongsTo
    {
        return $this->belongsTo(TypeResult::class, 'type_result_id');
    }

    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class, 'examen_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function group_populations(): BelongsToMany
    {
        return $this->belongsToMany(GroupePopulation::class, 'normal_value', 'element_paillasse_id', 'groupe_population_id')
            ->withPivot(['value', 'value_max', 'sign'])
            ->withTimestamps();
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class, 'element_paillasse_id');
    }

    public function catPredefinedList(): BelongsTo
    {
        return $this->belongsTo(CatPredefinedList::class, 'cat_predefined_list_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ElementPaillasse::class, 'element_paillasses_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ElementPaillasse::class, 'element_paillasses_id');
    }
}
