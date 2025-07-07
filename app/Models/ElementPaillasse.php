<?php

namespace App\Models;

use App\Models\Trait\UpdatingUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ElementPaillasse extends Model
{
    use UpdatingUser;

    protected $fillable = [
        'name',
        'unit',
        'numero_order',
        'category_element_result_id',
        'type_result_id',
        'examen_id',
        'created_by',
        'updated_by'
    ];

    public function categoryElementResult(): BelongsTo
    {
        return $this->belongsTo(CategoryElementResult::class, 'category_element_result_id');
    }

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
}
