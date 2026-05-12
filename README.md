# AgroControl

Sistema de gestion agricola para Pita Farms, construido con Laravel.

## Requisitos

- PHP 8.2+
- Composer
- MySQL
- Node.js y npm

## Puesta en marcha

1. Instalar dependencias de PHP con composer install.
2. Instalar dependencias de frontend con npm install.
3. Copiar .env.example a .env y configurar la base de datos.
4. Generar la clave de aplicacion con php artisan key:generate.
5. Ejecutar migraciones con php artisan migrate.
6. Iniciar el servidor con php artisan serve.

## Modulos principales

- Consumos
- Inventario
- Cultivos
- Cosechas
- Reporteria
- Notificaciones
