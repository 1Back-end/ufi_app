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

    public function roles(): BelongsToMany
    {
        $relation = $this->morphToMany(
            config('permission.models.role'),
            'model',
            config('permission.table_names.model_has_roles'),
            config('permission.column_names.model_morph_key'),
            app(PermissionRegistrar::class)->pivotRole
        )->withPivot(['created_by', 'updated_by', 'active'])
            ->withTimestamps();

        if (! app(PermissionRegistrar::class)->teams) {
            return $relation;
        }

        $teamsKey = app(PermissionRegistrar::class)->teamsKey;
        $relation->withPivot($teamsKey);
        $teamField = config('permission.table_names.roles').'.'.$teamsKey;

        return $relation->wherePivot($teamsKey, getPermissionsTeamId())
            ->where(fn ($q) => $q->whereNull($teamField)->orWhere($teamField, getPermissionsTeamId()));
    }

    public function permissions(): BelongsToMany
    {
        $relation = $this->morphToMany(
            config('permission.models.permission'),
            'model',
            config('permission.table_names.model_has_permissions'),
            config('permission.column_names.model_morph_key'),
            app(PermissionRegistrar::class)->pivotPermission
        )->withPivot(['created_by', 'updated_by', 'active'])
            ->withTimestamps();

        if (! app(PermissionRegistrar::class)->teams) {
            return $relation;
        }

        $teamsKey = app(PermissionRegistrar::class)->teamsKey;
        $relation->withPivot($teamsKey);

        return $relation->wherePivot($teamsKey, getPermissionsTeamId());
    }
}
