<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConsultantDuplicatesArchive extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'consultant_duplicates_archives';

    protected $fillable = [
        'original_id',
        'reference',
        'nom',
        'prenom',
        'telephone',
        'donnees_completes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'donnees_completes' => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
