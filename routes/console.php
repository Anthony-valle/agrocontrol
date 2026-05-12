<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Database\Seeders\AdminSeeder;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('system:reset-clean {--admin : Recrea solo los registros minimos del admin al finalizar} {--force : Ejecuta sin confirmacion}', function () {
    $debeContinuar = $this->option('force')
        || $this->confirm('Esto eliminara toda la base de datos actual y reiniciara los IDs. Deseas continuar?');

    if (! $debeContinuar) {
        $this->warn('Operacion cancelada.');

        return self::SUCCESS;
    }

    $this->components->info('Limpiando caches de Laravel...');
    $this->call('optimize:clear');

    $this->components->info('Eliminando todas las tablas, vistas y tipos de la base actual...');
    $this->call('db:wipe', [
        '--drop-views' => true,
        '--force' => true,
    ]);

    $this->components->info('Recreando el esquema desde las migraciones...');
    $this->call('migrate', ['--force' => true]);

    if ($this->option('admin')) {
        $this->components->info('Sembrando solo el administrador minimo del sistema...');
        $this->call('db:seed', [
            '--class' => AdminSeeder::class,
            '--force' => true,
        ]);
    }

    $this->newLine();
    $this->components->info('Base de datos reiniciada correctamente.');
    $this->line('Los IDs quedaron reiniciados porque las tablas se eliminaron y recrearon desde cero.');

    if ($this->option('admin')) {
        $this->line('Se recrearon solo los registros minimos del admin: empresa, sucursal, rol y usuario administrador.');
    } else {
        $this->line('No se recreo ningun dato semilla. La base quedo vacia.');
    }

    return self::SUCCESS;
})->purpose('Limpia toda la base, reinicia IDs y opcionalmente recrea solo el admin minimo');
