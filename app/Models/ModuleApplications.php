<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ModuleApplications extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'modules_applications';

    protected $fillable = [
        'id',
        'name',
        'slug',
        'icon',
        'description',
        'is_active',
        'created_by',
        'updated_by',
        'is_first_module'
    ];


    protected $appends = ['permissions_count'];

    // Relations facultatives avec User
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function permissions()
    {
        return $this->belongsToMany(
            Permission::class,
            'module_permission',
            'module_id',
            'permission_id'
        )->using(ModulePermission::class) // <-- modèle pivot avec UUID
        ->withTimestamps();
    }

    public function getPermissionsCountAttribute(): int
    {
        return $this->permissions()->count();
    }
}
