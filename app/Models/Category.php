<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Category extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'group_product_id',
        'description',
        'is_deleted',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function groupProduct()
    {
        return $this->belongsTo(GroupProduct::class, 'group_product_id');
    }
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_category', 'category_id', 'product_id');
    }

// Dans le modèle Fournisseurs


    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    //
}
