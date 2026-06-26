<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fournisseurs extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'fournisseurs';

    protected $fillable = [
        'full_name',
        'company_name',
        'address',
        'phone_number',
        'second_phone_number',
        'email',
        'business_registration_number',
        'website',
        'city',
        'country',
        'tax_number',
        'contact_person',
        'contact_person_phone',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
    public function setFullNameAttribute($value)
    {
        $this->attributes['full_name'] = mb_strtoupper($value, 'UTF-8');
    }

    public function products()
    {
        return $this->belongsToMany(
            Product::class,
            'product_fournisseur',
            'fournisseur_id',
            'product_id'
        );
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
