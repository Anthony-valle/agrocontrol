<?php

namespace App\Models;

use App\Traits\TracksDeletionMetadata;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Sucursal;
use Illuminate\Support\Facades\Storage;

class Empresa extends Model
{
    use HasFactory, SoftDeletes, TracksDeletionMetadata;

    protected $table = 'empresas';

    protected $fillable = [
        'nombre',
        'rtn',
        'nit',
        'direccion',
        'telefono',
        'email',
        'pais',
        'departamento',
        'tipo_empresa',
        'logo',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    // Relación con sucursales
    public function sucursal()
    {
        return $this->hasMany(Sucursale::class);
    }

    // Usuario que creó la empresa
    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Usuario que actualizó la empresa
    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getRtnAttribute(mixed $value)
    {
        return $value ?? ($this->attributes['nit'] ?? null);
    }

    public function getLogoUrlAttribute(): ?string
    {
        $logo = trim((string) ($this->attributes['logo'] ?? ''));

        if ($logo === '') {
            return null;
        }

        $rutaNormalizada = ltrim(str_replace('storage/', '', $logo), '/');

        if (Storage::disk('public')->exists($rutaNormalizada)) {
            $rutaAbsoluta = Storage::disk('public')->path($rutaNormalizada);
            $mimeType = mime_content_type($rutaAbsoluta) ?: 'image/png';
            $contenido = base64_encode(file_get_contents($rutaAbsoluta));

            return 'data:' . $mimeType . ';base64,' . $contenido;
        }

        if (file_exists(public_path($logo))) {
            return asset($logo);
        }

        if (file_exists(public_path('storage/' . $rutaNormalizada))) {
            return asset('storage/' . $rutaNormalizada);
        }

        return null;
    }
}

