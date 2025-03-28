<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Media extends Model
{
    protected $table = 'medias';

    protected $fillable = [
        'mediable', 'name', 'disk', 'path', 'filename', 'mimetype', 'extension',
    ];

    public function centre(): MorphTo
    {
        return $this->morphTo(Centre::class, 'mediable');
    }
}
