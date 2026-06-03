<?php

namespace App\Models;

use App\Models\Bodega;
use App\Traits\TracksDeletionMetadata;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use Notifiable, SoftDeletes, TracksDeletionMetadata;

    private const LEGACY_PERMISSION_ALIASES = [
        'mano_obra' => ['labores'],
        'preparacion_suelo' => ['labores'],
        'mecanizacion' => ['labores'],
        'reporte_mano_obra' => ['labores'],
    ];

    private ?string $imagenUsuarioUrlCache = null;

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

    private const MASS_IMPORT_ROLES = [
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
        'bodega_id_consumo',
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
        return $this->normalizeAccessPermissions($value);
    }

    public function setAccessPermissionsAttribute(mixed $value): void
    {
        $this->attributes['access_permissions'] = json_encode(
            $this->normalizeAccessPermissions($value),
            JSON_UNESCAPED_UNICODE
        );
    }

    public function getImagenUsuarioUrlAttribute(): string
    {
        if ($this->imagenUsuarioUrlCache !== null) {
            return $this->imagenUsuarioUrlCache;
        }

        $imagen = trim((string) ($this->attributes['imagen_usuario'] ?? ''));
        $fallback = asset('NiceAdmin/assets/img/default-user-avatar.svg');

        if ($imagen === '') {
            return $this->imagenUsuarioUrlCache = $fallback;
        }

        $rutaNormalizada = ltrim(str_replace('storage/', '', $imagen), '/');

        if (Storage::disk('public')->exists($rutaNormalizada)) {
            $rutaAbsoluta = Storage::disk('public')->path($rutaNormalizada);
            $mimeType = mime_content_type($rutaAbsoluta) ?: 'image/png';
            $contenido = base64_encode(file_get_contents($rutaAbsoluta));

            return $this->imagenUsuarioUrlCache = 'data:' . $mimeType . ';base64,' . $contenido;
        }

        if (file_exists(public_path($imagen))) {
            return $this->imagenUsuarioUrlCache = asset($imagen);
        }

        if (file_exists(public_path('storage/' . $rutaNormalizada))) {
            return $this->imagenUsuarioUrlCache = asset('storage/' . $rutaNormalizada);
        }

        return $this->imagenUsuarioUrlCache = $fallback;
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

    public function bodegaConsumo()
    {
        return $this->belongsTo(Bodega::class, 'bodega_id_consumo');
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

    public function canManageMassImports(): bool
    {
        return $this->hasAnyRole(self::MASS_IMPORT_ROLES);
    }

    public function isNotificador(): bool
    {
        return $this->hasRole('notificador');
    }

    public function hasAssignedConsumptionWarehouse(): bool
    {
        return ! empty($this->bodega_id_consumo);
    }

    public function requiresAssignedConsumptionWarehouse(): bool
    {
        return $this->isNotificador();
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
            if (in_array($permission, $permissions, true)) {
                return true;
            }

            foreach (self::LEGACY_PERMISSION_ALIASES[$permission] ?? [] as $legacyPermission) {
                if (in_array($legacyPermission, $permissions, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function normalizeRoleName(?string $role): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower(trim((string) $role))) ?? '';
    }

    private function normalizeAccessPermissions(mixed $value): array
    {
        if (is_array($value)) {
            return $this->sanitizeAccessPermissions($value);
        }

        if ($value === null || $value === '') {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (is_array($decoded)) {
                return $this->sanitizeAccessPermissions($decoded);
            }

            if (is_string($decoded)) {
                return $this->normalizeAccessPermissions($decoded);
            }

            $trimmed = trim($value, " \t\n\r\0\x0B\"'");
            if ($trimmed === '' || in_array($trimmed, ['[]', '{}', 'null'], true)) {
                return [];
            }

            return $this->sanitizeAccessPermissions(explode(',', $trimmed));
        }

        return [];
    }

    private function sanitizeAccessPermissions(array $permissions): array
    {
        return array_values(array_unique(array_filter(array_map(function (mixed $permission) {
            if (! is_string($permission)) {
                return null;
            }

            $normalized = trim($permission, " \t\n\r\0\x0B\"'");

            if ($normalized === '' || in_array($normalized, ['[]', '{}', 'null'], true)) {
                return null;
            }

            return $normalized;
        }, $permissions))));
    }
}

