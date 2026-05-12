<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Role;
use App\Models\Sucursale;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    // Mostrar formulario de login
    public function index()
    {
        $titulo = 'Login de Usuario';
        return view('modules.auth.login', compact('titulo'));
    }

    public function logear(Request $request)
    {
        // Validar datos
        $request->validate([
            'usuario' => 'required|string',
            'password' => 'required|string',
        ]);

        // Buscar usuario
        $user = User::where('usuario', $request->usuario)->first();

        // Validar usuario y contraseña
        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors(['usuario' => 'Credenciales incorrectas'])->withInput();
        }

        // Verificar estado (activo/inactivo)
        if ($user->estado == 0) {
            return back()->withErrors(['usuario' => 'Usuario inactivo'])->withInput();
        }

        // Iniciar sesión
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        // Redirigir al dashboard
        return redirect()->route('home');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    public function showForgotPasswordForm()
    {
        return view('modules.auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $validated = $request->validate([
            'usuario' => 'required|string|exists:users,usuario',
        ], [
            'usuario.required' => 'El usuario es obligatorio.',
            'usuario.exists' => 'El usuario ingresado no existe.',
        ]);

        $user = User::where('usuario', $validated['usuario'])->firstOrFail();

        if (! $user->estado) {
            return back()->withErrors(['usuario' => 'El usuario se encuentra inactivo.'])->withInput();
        }

        $token = Str::random(64);

        Cache::put(
            'password_reset:' . $token,
            ['usuario' => $user->usuario],
            now()->addMinutes(30)
        );

        $resetLink = route('password.reset.form', [
            'token' => $token,
            'usuario' => $user->usuario,
        ]);

        return back()
            ->with('success', 'Enlace temporal generado correctamente.')
            ->with('reset_link', $resetLink);
    }

    public function showResetPasswordForm(Request $request, string $token)
    {
        $usuario = (string) $request->query('usuario', '');
        $payload = Cache::get('password_reset:' . $token);

        if (! is_array($payload) || ($payload['usuario'] ?? null) !== $usuario) {
            return redirect()->route('password.request')
                ->withErrors(['usuario' => 'El enlace de recuperación no es válido o ya expiró.']);
        }

        return view('modules.auth.reset-password', [
            'token' => $token,
            'usuario' => $usuario,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'usuario' => 'required|string|exists:users,usuario',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'usuario.required' => 'El usuario es obligatorio.',
            'usuario.exists' => 'El usuario no existe.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $payload = Cache::get('password_reset:' . $validated['token']);

        if (! is_array($payload) || ($payload['usuario'] ?? null) !== $validated['usuario']) {
            return redirect()->route('password.request')
                ->withErrors(['usuario' => 'El enlace de recuperación no es válido o ya expiró.']);
        }

        $user = User::where('usuario', $validated['usuario'])->firstOrFail();
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        Cache::forget('password_reset:' . $validated['token']);

        return redirect()->route('login')->with('success', 'Contraseña actualizada correctamente.');
    }

    public function crearAdmin()
    {
        abort_unless(app()->environment('local'), 404);

        // 1. Empresa
        $empresa = Empresa::firstOrNew(['nombre' => 'Empresa Principal']);
        $empresa->forceFill($this->filterPersistedColumns('empresas', [
            'nombre' => 'Empresa Principal',
            'nit' => '00000000000000',
            'direccion' => 'Central',
            'telefono' => '0000-0000',
            'email' => 'admin@agrocontrol.local',
        ]));
        $empresa->save();

        // 2. Sucursal
        $sucursal = Sucursale::firstOrNew([
            'empresa_id' => $empresa->id,
            'nombre' => 'Sucursal Principal',
        ]);
        $sucursal->forceFill($this->filterPersistedColumns('sucursales', [
            'empresa_id' => $empresa->id,
            'nombre' => 'Sucursal Principal',
            'direccion' => 'Central',
            'telefono' => '0000-0000',
            'email' => 'sucursal@agrocontrol.local',
            'responsable' => 'Propietario',
            'estado' => true,
        ]));
        $sucursal->save();

        // 3. Rol
        $rol = Role::firstOrNew(['nombre' => 'propietario']);
        $rol->forceFill($this->filterPersistedColumns('roles', [
            'nombre' => 'propietario',
            'estado' => true,
        ]));
        $rol->save();

        // 4. Usuario admin
        $user = User::firstOrNew(['usuario' => 'propietario']);
        $user->forceFill($this->filterPersistedColumns('users', [
            'name' => 'Propietario',
            'email' => 'propietario@agrocontrol.local',
            'nombre_completo' => 'Propietario',
            'usuario' => 'propietario',
            'password' => Hash::make('propietario'),
            'rol_id' => $rol->id,
            'sucursal_id' => $sucursal->id,
            'empresa_id' => $empresa->id,
            'estado' => true,
            'imagen_usuario' => 'avatars/admin_avatar7.png',
            'access_permissions' => json_encode([]),
        ]));
        $user->save();

        return 'Administrador creado correctamente';
    }

    private function filterPersistedColumns(string $table, array $payload): array
    {
        $availableColumns = array_flip(Schema::getColumnListing($table));

        return array_intersect_key($payload, $availableColumns);
    }



}
