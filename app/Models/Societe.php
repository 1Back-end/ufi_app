<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Societe extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom_soc_cli',
        'tel_soc_cli',
        'Adress_soc_cli',
        'num_contrib_soc_cli',
        'email_soc_cli',
        'create_by_soc_cli',
        'updated_by_soc_cli',
    ];

    public function createBySocCli(): BelongsTo
    {
        return $this->belongsTo(User::class, 'create_by_soc_cli');
    }

    public function updatedBySocCli(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_soc_cli');
    }
}
