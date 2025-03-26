<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Contracts\Permission;
use Spatie\Permission\Events\RoleAttached;
use Spatie\Permission\Events\RoleDetached;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, SoftDeletes, HasApiTokens;

    protected $fillable = [
        'created_by', 'updated_by', 'login', 'email', 'password', 'nom_utilisateur', 'prenom', 'status',
        'connexion_counter', 'password_expiated_at', 'connected',
    ];

    protected $hidden = [
        'password'
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
