<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ModulePermission extends Pivot
{
    use HasFactory, SoftDeletes;

    protected $table = 'module_permission';


    protected $fillable = [
        'id',
        'module_uuid',
        'permission_id',
        'created_by',
        'updated_by',
    ];


    public function module()
    {
        return $this->belongsTo(ModuleApplications::class, 'model_id', 'id');
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class, 'permission_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
