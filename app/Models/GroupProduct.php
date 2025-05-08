<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GroupProduct extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'is_deleted',
    ];
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function categories()
    {
        return $this->hasMany(Category::class, 'group_product_id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    //
}
