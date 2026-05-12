<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\Role;
use App\Models\Sucursale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    // Listar usuarios
    public function index()
    {
        $titulo = 'Administrar Usuarios';
        $usuarios = Usuario::with(['rol', 'sucursal'])->get();

        return view('modules.usuarios.index', compact('titulo', 'usuarios'));
    }

    // Formulario crear
    public function create()
    {
        $roles = Role::where('estado', 1)->get();
        $sucursales = Sucursale::where('estado', 1)->get();

        return view('modules.usuarios.create', compact('roles', 'sucursales'));
    }

    // Guardar usuario
    public function store(Request $request)
    {
        $request->validate([
            'nombre_completo' => 'required|string|max:50',
            'usuario' => 'required|string|max:20|unique:users,usuario',
            'password' => 'required|string|min:6',
            'rol_id' => 'required|integer',
            'sucursal' => 'required|string|max:50',
        ]);

        Usuario::create([
            'nombre_completo' => $request->nombre_completo,
            'usuario' => $request->usuario,
            'password' => Hash::make($request->password),
            'rol_id' => $request->rol_id,
            'sucursal' => $request->sucursal,
            'estado' => 1,
        ]);

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario creado correctamente');
    }

    // Formulario editar
    public function edit(Usuario $usuario)
    {
        $roles = Role::where('estado', 1)->get();
        $sucursales = Sucursale::where('estado', 1)->get();

        return view('modules.usuarios.edit', compact('usuario', 'roles', 'sucursales'));
    }

    // Actualizar usuario
    public function update(Request $request, Usuario $usuario)
    {
        $request->validate([
            'nombre_completo' => 'required|string|max:50',
            'usuario' => 'required|string|max:20|unique:users,usuario,' . $usuario->id,
            'password' => 'nullable|string|min:6',
            'rol_id' => 'required|integer',
            'sucursal' => 'required|string|max:50',
        ]);

        $data = [
            'nombre_completo' => $request->nombre_completo,
            'usuario' => $request->usuario,
            'rol_id' => $request->rol_id,
            'sucursal' => $request->sucursal,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $usuario->update($data);

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario actualizado correctamente');
    }

    // Eliminar usuario
    public function destroy(Usuario $usuario)
    {
        $usuario->delete();

        return response()->json(['success' => true]);
    }
}
