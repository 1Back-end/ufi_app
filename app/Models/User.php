<?php

namespace App\Models;

use App\Models\Traits\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Contracts\Permission;
use Spatie\Permission\Events\RoleAttached;
use Spatie\Permission\Events\RoleDetached;
use Spatie\Permission\PermissionRegistrar;

class User extends Model
{
    use HasFactory, HasRoles, SoftDeletes;

    protected $fillable = [
        'created_by', 'updated_by', 'login', 'email', 'password', 'nom', 'prenom', 'status',
        'connexion_counter', 'password_expiated_at', 'connected',
    ];

    protected $casts = [
        'password_expiated_at' => 'datetime'
    ];

    public function centres(): BelongsToMany
    {
        return $this->belongsToMany(Centre::class, 'user_centre');
    }

    public function createRoles(): HasMany
    {
        return $this->hasMany(Role::class, 'created_by');
    }

    public function updateRoles(): HasMany
    {
        return $this->hasMany(Role::class, 'updated_by');
    }
}
