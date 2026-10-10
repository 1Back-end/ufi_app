<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CentrePermission extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'centre_permission';

    protected $fillable = [
        'centre_id',
        'permission_id',
        'created_by',
        'updated_by',
    ];

    public function centre()
    {
        return $this->belongsTo(Centre::class);
    }
    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
