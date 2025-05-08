<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OpsTblHospitalisation extends Model
{
    use HasFactory;
    protected $table = 'ops_tbl_hospitalisation';



    protected $fillable = [
        'name',
        'pu',
        'pu_default',
        'description',
        'created_by',
        'updated_by',
        'is_deleted',
    ];

    // Relation avec l'utilisateur qui a créé
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relation avec l'utilisateur qui a mis à jour
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    //
}
