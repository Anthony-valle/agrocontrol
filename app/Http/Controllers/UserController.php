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
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class UserController extends Controller
{
    // Mostrar todos los usuarios de la misma sucursal que el usuario logueado
    public function index()
    {
        $titulo = 'Configuración de Usuarios';
        /** @var \App\Models\User|null $currentUser */
        $currentUser = Auth::user();

        $users = $this->visibleUsersQuery($currentUser)
            ->with(['rol', 'sucursal', 'creador'])
            ->get();

        return view('modules.usuarios.index', compact('titulo', 'users'));
    }

    public function accessIndex(Request $request)
    {
        $titulo = 'Accesos por Usuario';
        /** @var \App\Models\User|null $currentUser */
        $currentUser = Auth::user();

        $users = $this->visibleUsersQuery($currentUser)
            ->with(['rol', 'sucursal', 'bodegaConsumo'])
            ->orderBy('nombre_completo')
            ->get();

        $selectedUserId = (int) ($request->integer('user_id') ?: ($users->first()->id ?? 0));
        $selectedUser = $users->firstWhere('id', $selectedUserId);
        $accessOptions = $this->accessOptions();
        $accessCatalog = $this->accessCatalog($accessOptions);
        $bodegas = $this->availableConsumptionWarehouses($currentUser);

        return view('modules.usuarios.access-index', compact('titulo', 'users', 'selectedUser', 'accessOptions', 'accessCatalog', 'bodegas'));
    }

    public function updateAccess(Request $request, User $user)
    {
        /** @var \App\Models\User|null $currentUser */
        $currentUser = Auth::user();

        if (! $this->visibleUsersQuery($currentUser)->whereKey($user->id)->exists()) {
            abort(403);
        }

        $validated = $request->validate([
            'bodega_id_consumo' => 'nullable|exists:bodegas,id',
        ] + $this->accessPermissionRules(), [
            'access_permissions.array' => 'Los accesos deben enviarse como un listado.',
            'access_permissions.*.in' => 'Uno o más accesos seleccionados no son válidos.',
            'bodega_id_consumo.exists' => 'La bodega seleccionada no es válida.',
        ]);

        $validated['bodega_id_consumo'] = $this->validateAccessWarehouseAssignment(
            $request->input('bodega_id_consumo'),
            $user,
            $currentUser
        );

        $user->update($this->filterPersistedColumns('users', [
            'access_permissions' => $validated['access_permissions'] ?? [],
            'bodega_id_consumo' => $validated['bodega_id_consumo'],
            'updated_by' => Auth::id(),
        ]));

        return redirect()
            ->route('usuarios.access.index', ['user_id' => $user->id])
            ->with('success', 'Accesos actualizados correctamente.');
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

        $accessOptions = $this->accessOptions();

        return view('modules.usuarios.create', compact('roles', 'sucursales', 'accessOptions'));
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
                'estado'          => 'required|in:0,1',
                'imagen_usuario'  => 'nullable|image|max:2048',
            ] + $this->accessPermissionRules(), [
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
                'access_permissions.array' => 'Los accesos deben enviarse como un listado.',
                'access_permissions.*.in' => 'Uno o más accesos seleccionados no son válidos.',
            ]);

            /** @var \App\Models\User|null $currentUser */
            $currentUser = Auth::user();
            if ($currentUser && ! $currentUser->isSuperUser()) {
                $validated['sucursal_id'] = $currentUser->sucursal_id;
            }

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
                'bodega_id_consumo' => null,
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

        $accessOptions = $this->accessOptions();

        return view('modules.usuarios.edit', compact('user', 'roles', 'sucursales', 'accessOptions'));
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
                'estado'          => 'required|in:0,1',
                'imagen_usuario'  => 'nullable|image|max:2048',
            ] + $this->accessPermissionRules(), [
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
                'access_permissions.array' => 'Los accesos deben enviarse como un listado.',
                'access_permissions.*.in' => 'Uno o más accesos seleccionados no son válidos.',
            ]);

            /** @var \App\Models\User|null $currentUser */
            $currentUser = Auth::user();
            if ($currentUser && ! $currentUser->isSuperUser()) {
                $validated['sucursal_id'] = $currentUser->sucursal_id;
            }

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
                'bodega_id_consumo' => $user->bodega_id_consumo,
                'access_permissions' => array_key_exists('access_permissions', $validated) ? ($validated['access_permissions'] ?? []) : ($user->access_permissions ?? []),
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
            'mano_obra' => 'Mano de Obra',
            'preparacion_suelo' => 'Actividades de Preparación de Suelo',
            'mecanizacion' => 'Mecanización',
            'reporte_mano_obra' => 'Reporte de Mano de Obra',
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

    private function accessCatalog(array $accessOptions): array
    {
        $catalog = [
            [
                'title' => 'Dashboard',
                'icon' => 'bi-bar-chart-line',
                'description' => 'Pantalla inicial del sistema con resumenes y accesos rapidos.',
                'items' => [
                    [
                        'permission' => null,
                        'label' => 'Dashboard',
                        'functions' => ['Dashboard'],
                        'summary' => 'Pantalla principal visible al iniciar sesión. No depende de un permiso individual en esta matriz.',
                        'editable' => false,
                    ],
                ],
            ],
            [
                'title' => 'Configuración General',
                'icon' => 'bi-gear-wide-connected',
                'description' => 'Define el acceso a la administracion base de empresa, sucursales, almacenes y lotes.',
                'items' => [
                    ['permission' => 'empresas', 'functions' => ['Datos de Empresa']],
                    ['permission' => 'sucursales', 'functions' => ['Gestión de Sucursales']],
                    ['permission' => 'bodegas', 'functions' => ['Gestión de Almacenes']],
                    ['permission' => 'lotes', 'functions' => ['Gestión de Lotes', 'Reporte de Lote y Cultivos']],
                ],
            ],
            [
                'title' => 'Módulo de Cultivos',
                'icon' => 'bi-tree',
                'description' => 'Controla la operacion del cultivo, labores, planes y movimientos agricolas asociados.',
                'items' => [
                    ['permission' => 'cultivos', 'functions' => ['Crear Cultivo', 'Reporte de Cultivos', 'Reporte General de Consumos', 'Rentabilidad']],
                    ['permission' => 'mano_obra', 'functions' => ['Mano de Obra']],
                    ['permission' => 'preparacion_suelo', 'functions' => ['Actividades de Preparación de Suelo']],
                    ['permission' => 'mecanizacion', 'functions' => ['Mecanización']],
                    ['permission' => 'reporte_mano_obra', 'functions' => ['Reporte de Mano de Obra']],
                    ['permission' => 'planes', 'functions' => ['Recetas y Planes']],
                    ['permission' => 'consumo', 'functions' => ['Consumo Cultivo', 'Reporte de Consumos', 'Cultivos Cerrados']],
                    ['permission' => 'cosecha', 'functions' => ['Gestión de Cosechas', 'Facturar Cosechas', 'Reporte de Cosechas', 'Cultivos Cerrados']],
                ],
            ],
            [
                'title' => 'Control de Inventario',
                'icon' => 'bi-box-seam',
                'description' => 'Agrupa catalogos de insumos, entradas, movimientos, ajustes y existencias.',
                'items' => [
                    ['permission' => 'insumos', 'functions' => ['Categorías de Insumos', 'Catálogo de Insumos', 'Insumos por Categoría']],
                    ['permission' => 'entrada', 'functions' => ['Registrar Entrada']],
                    ['permission' => 'traslado', 'functions' => ['Traslado entre Almacen']],
                    ['permission' => 'ajuste', 'functions' => ['Ajustes Almacen']],
                    ['permission' => 'inventarios', 'functions' => ['Stock Actual', 'Kardex de Movimientos', 'Reporte de Inventario', 'Facturas de Entradas']],
                ],
            ],
            [
                'title' => 'Reportería',
                'icon' => 'bi-bar-chart-line',
                'description' => 'Concentra los reportes del sistema segun el permiso funcional asociado.',
                'items' => [
                    ['permission' => 'lotes', 'functions' => ['Reporte de Lote y Cultivos']],
                    ['permission' => 'cultivos', 'functions' => ['Reporte de Cultivos', 'Reporte General de Consumos', 'Rentabilidad']],
                    ['permission' => 'consumo', 'functions' => ['Reporte de Consumos']],
                    ['permission' => 'cosecha', 'functions' => ['Reporte de Cosechas']],
                    ['permission' => 'inventarios', 'functions' => ['Reporte de Inventario', 'Facturas de Entradas']],
                    ['permission' => 'reporte_mano_obra', 'functions' => ['Reporte de Mano de Obra']],
                    ['permission' => 'insumos', 'functions' => ['Insumos por Categoría']],
                    [
                        'permission' => null,
                        'label' => 'Alertas y Notificaciones',
                        'functions' => ['Alertas y Notificaciones'],
                        'summary' => 'Disponible para usuarios superadministradores. Se muestra aqui como referencia del menu lateral.',
                        'editable' => false,
                    ],
                ],
            ],
            [
                'title' => 'Módulo de Compras',
                'icon' => 'bi-cart4',
                'description' => 'Permite revisar solicitudes y ordenes de compra del flujo documental.',
                'items' => [
                    ['permission' => 'compras', 'functions' => ['Indice documental', 'Validar llegada O.C.', 'Reporte O.C.']],
                ],
            ],
            [
                'title' => 'Notificación',
                'icon' => 'bi-bell',
                'description' => 'Agrupa las opciones operativas usadas por el menú de notificaciones y seguimiento.',
                'items' => [
                    ['permission' => 'consumo', 'functions' => ['Consumo Cultivo']],
                    ['permission' => 'cosecha', 'functions' => ['Gestión de Cosechas', 'Facturar Cosechas']],
                    ['permission' => 'mecanizacion', 'functions' => ['Mecanización']],
                ],
            ],
            [
                'title' => 'Seguridad y Acceso',
                'icon' => 'bi-people-fill',
                'description' => 'Administra usuarios y estructuras de permisos del sistema.',
                'items' => [
                    ['permission' => 'usuarios', 'functions' => ['Lista de Usuarios', 'Accesos por Usuario']],
                    ['permission' => 'roles', 'functions' => ['Roles']],
                ],
            ],
            [
                'title' => 'Sincronización Offline',
                'icon' => 'bi-arrow-repeat',
                'description' => 'Entrada de sincronizacion local disponible desde el menu lateral administrativo.',
                'items' => [
                    [
                        'permission' => null,
                        'label' => 'Sincronización Offline',
                        'functions' => ['Abrir bandeja de sincronización'],
                        'summary' => 'Acceso operativo del sistema offline. Se muestra como menu informativo porque no depende de un permiso guardado en access_permissions.',
                        'editable' => false,
                    ],
                ],
            ],
            [
                'title' => 'Soporte',
                'icon' => 'bi-life-preserver',
                'description' => 'Funciones de soporte tecnico y mantenimiento visibles en el menu lateral.',
                'items' => [
                    [
                        'permission' => null,
                        'label' => 'Soporte Técnico',
                        'functions' => ['Soporte Técnico'],
                        'summary' => 'Menu de soporte para atencion tecnica general. Se muestra como referencia del menu lateral.',
                        'editable' => false,
                    ],
                    [
                        'permission' => null,
                        'label' => 'Mantenimiento del Sistema',
                        'functions' => ['Backup del Sistema', 'Recuperar Eliminados'],
                        'summary' => 'Opciones adicionales visibles para superusuarios dentro del menu de soporte.',
                        'editable' => false,
                    ],
                ],
            ],
        ];

        $knownPermissions = collect($catalog)
            ->flatMap(fn (array $group) => collect($group['items'])->pluck('permission'))
            ->unique()
            ->values();

        $otherItems = collect($accessOptions)
            ->reject(fn (string $label, string $permission) => $knownPermissions->contains($permission))
            ->map(fn (string $label, string $permission) => [
                'permission' => $permission,
                'functions' => [$label],
            ])
            ->values()
            ->all();

        if ($otherItems !== []) {
            $catalog[] = [
                'title' => 'Otros accesos detectados',
                'icon' => 'bi-stars',
                'description' => 'Permisos encontrados en la configuracion actual que no estan mapeados a un menu principal.',
                'items' => $otherItems,
            ];
        }

        return collect($catalog)
            ->map(function (array $group) use ($accessOptions) {
                $items = collect($group['items'])
                    ->filter(fn (array $item) => ($item['permission'] ?? null) === null || array_key_exists($item['permission'], $accessOptions))
                    ->map(function (array $item) use ($accessOptions) {
                        $permission = $item['permission'] ?? null;
                        $usesAssignedWarehouse = is_string($permission) && in_array($permission, ['consumo', 'entrada', 'traslado', 'ajuste', 'inventarios'], true);

                        return [
                            'permission' => $permission,
                            'label' => $item['label'] ?? ($accessOptions[$permission] ?? $this->formatPermissionLabel((string) $permission)),
                            'functions' => array_values(array_unique($item['functions'])),
                            'summary' => $item['summary'] ?? ($permission ? $this->permissionSummary($permission, $item['functions']) : 'Elemento informativo del menu lateral.'),
                            'uses_assigned_warehouse' => $item['uses_assigned_warehouse'] ?? $usesAssignedWarehouse,
                            'editable' => $item['editable'] ?? ($permission !== null),
                        ];
                    })
                    ->values()
                    ->all();

                return [
                    'title' => $group['title'],
                    'icon' => $group['icon'],
                    'description' => $group['description'],
                    'items' => $items,
                ];
            })
            ->filter(fn (array $group) => $group['items'] !== [])
            ->values()
            ->all();
    }

    private function accessPermissionRules(): array
    {
        return [
            'access_permissions' => 'nullable|array',
            'access_permissions.*' => [
                'string',
                Rule::in(array_keys($this->accessOptions())),
            ],
        ];
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

    private function visibleUsersQuery(?User $currentUser)
    {
        return User::query()->when(
            $currentUser && ! $currentUser->isSuperUser(),
            fn ($query) => $query->where('sucursal_id', $currentUser->sucursal_id)
        );
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

    private function validateAccessWarehouseAssignment(mixed $warehouseId, User $user, ?User $currentUser): ?int
    {
        $warehouseId = filled($warehouseId) ? (int) $warehouseId : null;

        if ($user->requiresAssignedConsumptionWarehouse() && ! $warehouseId) {
            throw ValidationException::withMessages([
                'bodega_id_consumo' => 'Debes asignar una bodega de consumo a este usuario.',
            ]);
        }

        if (! $warehouseId) {
            return null;
        }

        $bodegaQuery = Bodega::query()->whereKey($warehouseId);

        if ($currentUser && ! $currentUser->isSuperUser()) {
            $bodegaQuery->where('sucursal_id', $currentUser->sucursal_id);
        }

        $bodegaQuery->where('sucursal_id', $user->sucursal_id);

        $bodega = $bodegaQuery->first();

        if (! $bodega) {
            throw ValidationException::withMessages([
                'bodega_id_consumo' => 'La bodega seleccionada no pertenece a la sucursal del usuario o no está disponible.',
            ]);
        }

        return (int) $bodega->id;
    }

    private function permissionSummary(string $permission, array $functions): string
    {
        return match ($permission) {
            'empresas' => 'Usara este acceso para editar los datos generales de la empresa y su configuracion principal.',
            'sucursales' => 'Usara este acceso para crear, editar y consultar las sucursales disponibles.',
            'bodegas' => 'Usara este acceso para administrar almacenes o bodegas y su disponibilidad operativa.',
            'lotes' => 'Usara este acceso para controlar lotes y revisar su relacion con los cultivos y reportes.',
            'cultivos' => 'Usara este acceso para abrir cultivos, consultar avances y revisar rentabilidad y consumos.',
            'labores' => 'Usara este acceso legado para abrir todas las opciones de labores del modulo de cultivos.',
            'mano_obra' => 'Usara este acceso solo para entrar y trabajar en Mano de Obra.',
            'preparacion_suelo' => 'Usara este acceso solo para entrar a Actividades de Preparación de Suelo.',
            'mecanizacion' => 'Usara este acceso solo para entrar a Mecanización.',
            'reporte_mano_obra' => 'Usara este acceso solo para consultar el Reporte de Mano de Obra.',
            'planes' => 'Usara este acceso para crear y mantener recetas o planes de cultivo.',
            'consumo' => 'Usara este acceso para registrar consumos del cultivo y normalmente operara con la bodega de consumo asignada.',
            'cosecha' => 'Usara este acceso para gestionar cosechas, facturacion asociada y reportes de cierre.',
            'insumos' => 'Usara este acceso para consultar y mantener categorias e insumos del inventario.',
            'entrada' => 'Usara este acceso para registrar entradas de inventario y alimentar existencias por almacen.',
            'traslado' => 'Usara este acceso para mover existencias entre almacenes o bodegas.',
            'ajuste' => 'Usara este acceso para realizar ajustes manuales en el inventario del almacen.',
            'inventarios' => 'Usara este acceso para consultar existencias, kardex y reportes de inventario por almacen.',
            'compras' => 'Usara este acceso para solicitudes, ordenes de compra y validaciones documentales.',
            'usuarios' => 'Usara este acceso para administrar usuarios y revisar su matriz de accesos.',
            'roles' => 'Usara este acceso para mantener los roles y su estructura operativa.',
            default => 'Usara este acceso para entrar a: ' . implode(', ', array_slice($functions, 0, 3)) . '.',
        };
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
