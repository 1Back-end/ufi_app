<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Centre extends Model
{
    use HasFactory;
    protected $fillable = [
        'nom_centre', 'tel_centre', 'numero_contribuable_centre',
        'registre_com_centre', 'fax_centre', 'email_centre',
        'numero_autorisation_centre', 'logo_centre', 'date_creation_centre',
    ];

    protected function casts()
    {
        return [
            'date_creation_centre' => 'timestamp',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_centre', 'centre_id', 'user_id');
    }
}
