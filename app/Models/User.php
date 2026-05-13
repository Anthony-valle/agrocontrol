<?php

namespace App\Models;

use App\Traits\TracksDeletionMetadata;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use Notifiable, SoftDeletes, TracksDeletionMetadata;

    private const SUPER_USER_ROLES = [
        'admin',
        'administrador',
        'programador',
        'propietario',
        'superadmin',
        'super administrador',
        'super-administrador',
    ];

    private const SENSITIVE_ACTION_ROLES = [
        'admin',
        'administrador',
        'propietario',
        'superadmin',
        'super administrador',
        'super-administrador',
    ];

    protected $fillable = [
        'name',
        'nombre_completo',
        'email',
        'usuario',
        'password',
        'imagen_usuario',
        'estado',
        'sucursal_id',
        'rol_id',
        'access_permissions',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'access_permissions' => 'array',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $attributes = [
        'access_permissions' => '[]',
    ];

    public function getAccessPermissionsAttribute(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if ($value === null || $value === '') {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }

            $parts = array_values(array_filter(array_map('trim', explode(',', $value)), static fn ($item) => $item !== ''));
            if ($parts !== []) {
                return $parts;
            }
        }

        return [];
    }

    public function getImagenUsuarioUrlAttribute(): string
    {
        $imagen = trim((string) ($this->attributes['imagen_usuario'] ?? ''));
        $fallback = asset('NiceAdmin/assets/img/default-user-avatar.svg');

        if ($imagen === '') {
            return $fallback;
        }

        $rutaNormalizada = ltrim(str_replace('storage/', '', $imagen), '/');

        if (Storage::disk('public')->exists($rutaNormalizada)) {
            $rutaAbsoluta = Storage::disk('public')->path($rutaNormalizada);
            $mimeType = mime_content_type($rutaAbsoluta) ?: 'image/png';
            $contenido = base64_encode(file_get_contents($rutaAbsoluta));

            return 'data:' . $mimeType . ';base64,' . $contenido;
        }

        if (file_exists(public_path($imagen))) {
            return asset($imagen);
        }

        if (file_exists(public_path('storage/' . $rutaNormalizada))) {
            return asset('storage/' . $rutaNormalizada);
        }

        return $fallback;
    }

    // Relaciones
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursale::class);
    }

    public function rol()
    {
        return $this->belongsTo(Role::class);
    }

    public function creador()
    {
        return $this->belongsTo(self::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(self::class, 'updated_by');
    }

    public function hasRole(string $nombre): bool
    {
        return $this->normalizeRoleName($this->rol->nombre ?? '') === $this->normalizeRoleName($nombre);
    }

    public function isProgramador(): bool
    {
        return $this->hasRole('programador');
    }

    public function isSuperUser(): bool
    {
        return $this->hasAnyRole(self::SUPER_USER_ROLES);
    }

    public function hasAnyRole(array $roles): bool
    {
        $currentRole = $this->normalizeRoleName($this->rol->nombre ?? '');

        return in_array($currentRole, array_map(fn ($role) => $this->normalizeRoleName($role), $roles), true);
    }

    public function canManageSensitiveActions(): bool
    {
        return $this->hasAnyRole(self::SENSITIVE_ACTION_ROLES);
    }

    public function hasAccess(string $permission): bool
    {
        // Roles con acceso global
        if ($this->isSuperUser()) {
            return true;
        }

        // Si tiene permisos específicos asignados, usar esos
        $permissions = $this->access_permissions ?? [];
        if (!empty($permissions)) {
            return in_array($permission, $permissions);
        }

        return false;
    }

    private function normalizeRoleName(?string $role): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower(trim((string) $role))) ?? '';
    }
}

