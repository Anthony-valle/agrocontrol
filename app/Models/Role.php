<?php

namespace App\Models;

use App\Traits\TracksDeletionMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use SoftDeletes, TracksDeletionMetadata;

    protected $fillable = [
        'nombre',
        'descripcion',
        'created_by'
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function permisos()
    {
        return $this->belongsToMany(Permiso::class);
    }

    public function creador()
    {
        return $this->belongsToMany(User::class);
    }
}
