<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\Role;
use App\Models\Sucursale;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $empresa = Empresa::firstOrCreate(
            ['nombre' => 'Empresa Principal'],
            [
                'direccion' => 'Casa matriz',
                'telefono' => '0000-0000',
                'email' => 'admin@agrocontrol.local',
            ]
        );

        $sucursal = Sucursale::firstOrCreate(
            ['nombre' => 'Matriz', 'empresa_id' => $empresa->id],
            [
                'direccion' => 'Casa matriz',
                'telefono' => '0000-0000',
                'email' => 'matriz@agrocontrol.local',
                'responsable' => 'Administrador',
                'estado' => true,
            ]
        );

        $rol = Role::firstOrCreate(['nombre' => 'admin'], ['estado' => true]);

        User::firstOrCreate(
            ['usuario' => 'admin'],
            [
                'nombre_completo' => 'Administrador',
                'password' => Hash::make('admin'),
                'rol_id' => $rol->id,
                'sucursal_id' => $sucursal->id,
                'estado' => true,
                'imagen_usuario' => 'avatars/admin_avatar7.png',
                'access_permissions' => [],
            ]
        );
    }
}
