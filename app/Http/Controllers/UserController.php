<?php

namespace App\Http\Controllers;

use App\Models\Bodega;
use App\Models\Role;
use App\Models\Sucursale;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class UserController extends Controller
{
    // Mostrar todos los usuarios de la misma sucursal que el usuario logueado
    public function index()
    {
        $titulo = 'Configuración de Usuarios';
        /** @var \App\Models\User|null $currentUser */
        $currentUser = Auth::user();

        if ($currentUser && $currentUser->isSuperUser()) {
            $users = User::with(['rol', 'sucursal', 'creador'])->get();
        } else {
            $users = User::with(['rol', 'sucursal', 'creador'])
                ->where('sucursal_id', $currentUser?->sucursal_id)
                ->get();
        }

        return view('modules.usuarios.index', compact('titulo', 'users'));
    }

    // Formulario para crear un nuevo usuario
    public function create()
    {
        $roles = Role::all();
        /** @var \App\Models\User|null $currentUser */
        /** @var \App\Models\User|null $currentUser */
        $currentUser = Auth::user();

        if ($currentUser && $currentUser->isSuperUser()) {
            $sucursales = Sucursale::where('estado', 1)->get();
        } else {
            $sucursales = Sucursale::where('estado', 1)
                ->where('id', $currentUser?->sucursal_id)
                ->get();
        }

        $bodegas = $this->availableConsumptionWarehouses($currentUser);

        $accessOptions = $this->accessOptions();

        return view('modules.usuarios.create', compact('roles', 'sucursales', 'bodegas', 'accessOptions'));
    }

    // Método para obtener sucursales vía AJAX (Ruta: /get-sucursales/{id})
    public function getSucursales(int $sucursal_id)
    {
        // En este contexto, si ya no filtras por empresa, podrías retornar 
        // todas o simplemente mantener la lógica de búsqueda por ID si es necesario.
        $sucursales = Sucursale::where('id', $sucursal_id)
                    ->where('estado', 1)
                    ->get(['id', 'nombre']);

        return response()->json($sucursales);
    }

    // Guardar nuevo usuario
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre_completo' => 'required|string|max:50',
                'email'           => 'nullable|string|email|max:255|unique:users,email',
                'usuario'         => 'required|string|max:20|unique:users,usuario',
                'password'        => 'required|min:6|confirmed',
                'rol_id'          => 'required|exists:roles,id',
                'sucursal_id'     => 'required|exists:sucursales,id',
                'bodega_id_consumo' => 'nullable|exists:bodegas,id',
                'estado'          => 'required|in:0,1',
                'imagen_usuario'  => 'nullable|image|max:2048',
                'access_permissions' => 'nullable|array',
                'access_permissions.*' => 'string',
            ], [
                'nombre_completo.required' => 'El nombre completo es obligatorio',
                'nombre_completo.string' => 'El nombre completo debe ser texto',
                'nombre_completo.max' => 'El nombre completo no puede exceder 50 caracteres',
                'email.email' => 'El correo electrónico no es válido',
                'email.max' => 'El correo electrónico no puede exceder 255 caracteres',
                'email.unique' => 'El correo electrónico ya ha sido registrado',
                'usuario.required' => 'El usuario es obligatorio',
                'usuario.string' => 'El usuario debe ser texto',
                'usuario.max' => 'El usuario no puede exceder 20 caracteres',
                'usuario.unique' => 'El usuario ya ha sido registrado',
                'password.required' => 'La contraseña es obligatoria',
                'password.min' => 'La contraseña debe tener mínimo 6 caracteres',
                'password.confirmed' => 'Las contraseñas no coinciden',
                'rol_id.required' => 'El rol es obligatorio',
                'rol_id.exists' => 'El rol seleccionado no es válido',
                'sucursal_id.required' => 'La sucursal es obligatoria',
                'sucursal_id.exists' => 'La sucursal seleccionada no es válida',
                'estado.required' => 'El estado es obligatorio',
                'estado.in' => 'El estado debe ser válido',
                'imagen_usuario.image' => 'La imagen debe ser un archivo de imagen',
                'imagen_usuario.max' => 'La imagen no puede exceder 2MB',
            ]);

            /** @var \App\Models\User|null $currentUser */
            $currentUser = Auth::user();
            if ($currentUser && ! $currentUser->isSuperUser()) {
                $validated['sucursal_id'] = $currentUser->sucursal_id;
            }

            $rolSeleccionado = Role::findOrFail((int) $validated['rol_id']);
            $validated['bodega_id_consumo'] = $this->resolveAssignedWarehouseValue(
                $request->input('bodega_id_consumo'),
                $rolSeleccionado,
                $validated['sucursal_id'] ?? null,
                $currentUser
            );

            $resolvedEmail = $this->resolveUserEmail(
                $validated['email'] ?? null,
                $validated['usuario'],
            );

            $usuario = User::create($this->filterPersistedColumns('users', [
                'name'            => $validated['nombre_completo'],
                'nombre_completo' => $validated['nombre_completo'],
                'email'           => $resolvedEmail,
                'usuario'         => $validated['usuario'],
                'password'        => Hash::make($validated['password']),
                'rol_id'          => $validated['rol_id'],
                'sucursal_id'     => $validated['sucursal_id'],
                'bodega_id_consumo' => $validated['bodega_id_consumo'],
                'access_permissions' => $validated['access_permissions'] ?? [],
                'imagen_usuario'  => $request->file('imagen_usuario')?->store('usuarios', 'public'),
                'estado'          => (bool) $validated['estado'],
                'created_by'      => Auth::id(),
            ]));

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json(['success' => 'Usuario registrado correctamente'], 200);
            }

            return redirect()->route('usuarios.index')->with('success', 'Usuario registrado correctamente');
        } catch (\Exception $e) {
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Ocurrió un error al guardar el usuario. Por favor, intenta de nuevo.',
                    'error' => 'Error: ' . $e->getMessage()
                ], 500);
            }
            throw $e;
        }
    }

    // Formulario para editar usuario
    public function edit(User $user)
    {
        $roles = Role::all();
        /** @var \App\Models\User|null $currentUser */
        $currentUser = Auth::user();

        if ($currentUser && $currentUser->isSuperUser()) {
            $sucursales = Sucursale::where('estado', 1)->get();
        } else {
            $sucursales = Sucursale::where('estado', 1)
                ->where('id', $currentUser?->sucursal_id)
                ->get();
        }

        $bodegas = $this->availableConsumptionWarehouses($currentUser);

        $accessOptions = $this->accessOptions();

        return view('modules.usuarios.edit', compact('user', 'roles', 'sucursales', 'bodegas', 'accessOptions'));
    }

    // Actualizar usuario
    public function update(Request $request, User $user)
    {
        try {
            $validated = $request->validate([
                'nombre_completo' => 'required|string|max:50',
                'email'           => 'nullable|string|email|max:255|unique:users,email,' . $user->id,
                'usuario'         => 'required|string|max:20|unique:users,usuario,' . $user->id,
                'password'        => 'nullable|min:6|confirmed',
                'rol_id'          => 'required|exists:roles,id',
                'sucursal_id'     => 'required|exists:sucursales,id',
                'bodega_id_consumo' => 'nullable|exists:bodegas,id',
                'estado'          => 'required|in:0,1',
                'imagen_usuario'  => 'nullable|image|max:2048',
                'access_permissions' => 'nullable|array',
                'access_permissions.*' => 'string',
            ], [
                'nombre_completo.required' => 'El nombre completo es obligatorio',
                'nombre_completo.string' => 'El nombre completo debe ser texto',
                'nombre_completo.max' => 'El nombre completo no puede exceder 50 caracteres',
                'email.email' => 'El correo electrónico no es válido',
                'email.max' => 'El correo electrónico no puede exceder 255 caracteres',
                'email.unique' => 'El correo electrónico ya ha sido registrado',
                'usuario.required' => 'El usuario es obligatorio',
                'usuario.string' => 'El usuario debe ser texto',
                'usuario.max' => 'El usuario no puede exceder 20 caracteres',
                'usuario.unique' => 'El usuario ya ha sido registrado',
                'password.min' => 'La contraseña debe tener mínimo 6 caracteres',
                'password.confirmed' => 'Las contraseñas no coinciden',
                'rol_id.required' => 'El rol es obligatorio',
                'rol_id.exists' => 'El rol seleccionado no es válido',
                'sucursal_id.required' => 'La sucursal es obligatoria',
                'sucursal_id.exists' => 'La sucursal seleccionada no es válida',
                'estado.required' => 'El estado es obligatorio',
                'estado.in' => 'El estado debe ser válido',
                'imagen_usuario.image' => 'La imagen debe ser un archivo de imagen',
                'imagen_usuario.max' => 'La imagen no puede exceder 2MB',
            ]);

            /** @var \App\Models\User|null $currentUser */
            $currentUser = Auth::user();
            if ($currentUser && ! $currentUser->isSuperUser()) {
                $validated['sucursal_id'] = $currentUser->sucursal_id;
            }

            $rolSeleccionado = Role::findOrFail((int) $validated['rol_id']);
            $validated['bodega_id_consumo'] = $this->resolveAssignedWarehouseValue(
                $request->input('bodega_id_consumo'),
                $rolSeleccionado,
                $validated['sucursal_id'] ?? null,
                $currentUser
            );

            $resolvedEmail = $this->resolveUserEmail(
                $validated['email'] ?? $user->email,
                $validated['usuario'],
                $user->id,
            );

            $user->update($this->filterPersistedColumns('users', [
                'name'            => $validated['nombre_completo'],
                'nombre_completo' => $validated['nombre_completo'],
                'email'           => $resolvedEmail,
                'usuario'         => $validated['usuario'],
                'rol_id'          => $validated['rol_id'],
                'sucursal_id'     => $validated['sucursal_id'],
                'bodega_id_consumo' => $validated['bodega_id_consumo'],
                'access_permissions' => $validated['access_permissions'] ?? [],
                'estado'          => (bool) $validated['estado'],
                'updated_by'      => Auth::id(),
                'password'        => $validated['password'] ? Hash::make($validated['password']) : $user->password,
                'imagen_usuario'  => $request->hasFile('imagen_usuario') ? $request->file('imagen_usuario')->store('usuarios', 'public') : $user->imagen_usuario,
            ]));

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json(['success' => 'Usuario actualizado correctamente'], 200);
            }

            return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado correctamente');
        } catch (\Exception $e) {
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Ocurrió un error al actualizar el usuario. Por favor, intenta de nuevo.',
                    'error' => 'Error: ' . $e->getMessage()
                ], 500);
            }
            throw $e;
        }
    }

    // Eliminar usuario
    public function destroy(User $user)
    {
        $user->delete();

        if (request()->ajax() || request()->expectsJson()) {
            return response()->json(['success' => 'Usuario eliminado correctamente'], 200);
        }

        return redirect()->back()->with('success', 'Usuario eliminado correctamente');
    }

    public function resetPassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ], [
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener mínimo 6 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $user->update($this->filterPersistedColumns('users', [
            'password' => Hash::make($validated['password']),
            'updated_by' => Auth::id(),
        ]));

        return response()->json([
            'success' => 'Contraseña actualizada correctamente.',
        ]);
    }

    public function revealTemporaryPassword(User $user)
    {
        /** @var \App\Models\User|null $currentUser */
        $currentUser = Auth::user();

        if (! $currentUser || ! $currentUser->hasAnyRole(['propietario', 'admin'])) {
            return response()->json([
                'message' => 'No autorizado para generar contraseñas temporales.',
            ], 403);
        }

        $temporaryPassword = Str::upper(Str::random(10));

        $user->update($this->filterPersistedColumns('users', [
            'password' => Hash::make($temporaryPassword),
            'updated_by' => Auth::id(),
        ]));

        return response()->json([
            'success' => 'Contraseña temporal generada correctamente.',
            'temporary_password' => $temporaryPassword,
            'warning' => 'La contraseña temporal solo se muestra una vez. Compártela de forma segura.',
        ]);
    }

    private function accessOptions(): array
    {
        $labels = [
            'empresas' => 'Empresas',
            'sucursales' => 'Sucursales',
            'bodegas' => 'Bodegas',
            'lotes' => 'Lotes',
            'cultivos' => 'Cultivos',
            'labores' => 'Labores',
            'planes' => 'Planes / Recetas',
            'consumo' => 'Consumo Cultivo',
            'cosecha' => 'Cosecha Cultivo',
            'insumos' => 'Insumos',
            'entrada' => 'Entrada Inventario',
            'traslado' => 'Traslado Almacén',
            'ajuste' => 'Ajuste Inventario',
            'inventarios' => 'Inventarios',
            'compras' => 'Compras / Solicitudes',
            'roles' => 'Roles',
            'usuarios' => 'Usuarios',
        ];

        $permissions = collect(array_keys($labels))
            ->merge($this->discoverSidebarPermissions())
            ->merge($this->discoverPersistedPermissions())
            ->map(fn (mixed $permission) => $this->normalizePermissionToken($permission))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return $permissions
            ->mapWithKeys(fn (string $permission) => [
                $permission => $labels[$permission] ?? $this->formatPermissionLabel($permission),
            ])
            ->all();
    }

    private function discoverSidebarPermissions(): array
    {
        $sidebarPath = resource_path('views/shared/aside.blade.php');
        if (! is_file($sidebarPath)) {
            return [];
        }

        $contents = file_get_contents($sidebarPath);
        if ($contents === false) {
            return [];
        }

        preg_match_all("/hasAccess\('([^']+)'\)/", $contents, $matches);

        return $matches[1] ?? [];
    }

    private function discoverPersistedPermissions(): array
    {
        return User::query()
            ->get(['access_permissions'])
            ->flatMap(function (User $user) {
                return $user->access_permissions ?? [];
            })
            ->map(fn (mixed $permission) => $this->normalizePermissionToken($permission))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizePermissionToken(mixed $permission): ?string
    {
        if (! is_string($permission)) {
            return null;
        }

        $normalized = trim($permission);
        if ($normalized === '') {
            return null;
        }

        if (in_array($normalized, ['[]', '[""]', "['']", '{}', 'null'], true)) {
            return null;
        }

        return $normalized;
    }

    private function formatPermissionLabel(string $permission): string
    {
        $normalized = str_replace(['_', '-'], ' ', $permission);

        return Str::headline($normalized);
    }

    private function filterPersistedColumns(string $table, array $payload): array
    {
        $availableColumns = array_flip(Schema::getColumnListing($table));

        return array_intersect_key($payload, $availableColumns);
    }

    private function availableConsumptionWarehouses(?User $currentUser)
    {
        return Bodega::query()
            ->when(
                Schema::hasColumn('bodegas', 'estado'),
                fn ($query) => $query->where('estado', 1)
            )
            ->when(
                $currentUser && ! $currentUser->isSuperUser(),
                fn ($query) => $query->where('sucursal_id', $currentUser->sucursal_id)
            )
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'sucursal_id']);
    }

    private function resolveAssignedWarehouseValue(mixed $warehouseId, Role $rol, mixed $sucursalId, ?User $currentUser): ?int
    {
        $normalizedRole = preg_replace('/[^a-z0-9]/', '', strtolower(trim((string) $rol->nombre))) ?? '';
        $requiresWarehouse = $normalizedRole === 'notificador';
        $warehouseId = filled($warehouseId) ? (int) $warehouseId : null;

        if ($requiresWarehouse && ! $warehouseId) {
            abort(response()->json([
                'errors' => ['bodega_id_consumo' => ['Debes asignar una bodega de consumo al usuario notificador.']],
            ], 422));
        }

        if (! $warehouseId) {
            return null;
        }

        $bodegaQuery = Bodega::query()->whereKey($warehouseId);

        if ($currentUser && ! $currentUser->isSuperUser()) {
            $bodegaQuery->where('sucursal_id', $currentUser->sucursal_id);
        }

        if ($sucursalId !== null) {
            $bodegaQuery->where('sucursal_id', $sucursalId);
        }

        $bodega = $bodegaQuery->first();

        if (! $bodega) {
            abort(response()->json([
                'errors' => ['bodega_id_consumo' => ['La bodega asignada no pertenece a la sucursal seleccionada o no está disponible.']],
            ], 422));
        }

        return (int) $bodega->id;
    }

    private function resolveUserEmail(?string $email, string $usuario, ?int $ignoreUserId = null): string
    {
        $email = trim((string) $email);
        if ($email !== '') {
            return $email;
        }

        $base = Str::slug($usuario, '.');
        if ($base === '') {
            $base = 'usuario';
        }

        $candidate = $base . '@agrocontrol.local';
        $suffix = 1;

        while ($this->emailExists($candidate, $ignoreUserId)) {
            $candidate = $base . '.' . $suffix . '@agrocontrol.local';
            $suffix++;
        }

        return $candidate;
    }

    private function emailExists(string $email, ?int $ignoreUserId = null): bool
    {
        $query = User::where('email', $email);

        if ($ignoreUserId !== null) {
            $query->where('id', '!=', $ignoreUserId);
        }

        return $query->exists();
    }
}
