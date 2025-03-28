<?php

namespace App\Models;

use App\Models\Trait\UpdatingUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Societe extends Model
{
    use HasFactory, UpdatingUser, SoftDeletes;

    protected $fillable = [
        'nom_soc_cli',
        'tel_soc_cli',
        'Adress_soc_cli',
        'num_contrib_soc_cli',
        'email_soc_cli',
        'created_by',
        'updated_by',
    ];

    public function createBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'societe_id');
    }
}
