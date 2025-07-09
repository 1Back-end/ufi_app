<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElementPaillasseExamen extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'unit',
        'order_number',
        'category_element_result_id',
        'type_result_id',
    ];

    public function categoryElementResult()
    {
        return $this->belongsTo(CategoryElementResult::class);
    }

    public function typeResult()
    {
        return $this->belongsTo(TypeResult::class);
    }
}
