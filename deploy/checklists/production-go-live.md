# Checklist de Go-Live

## Antes del despliegue

- APP_ENV=production
- APP_DEBUG=false
- APP_URL con dominio real
- APP_KEY cargada
- Credenciales de base de datos validadas
- HTTPS activo y SESSION_SECURE_COOKIE=true
- QUEUE_CONNECTION configurada para database o redis, no sync
- CACHE_STORE configurado para database o redis
- SESSION_DRIVER configurado para database o redis
- Worker persistente configurado con Supervisor o systemd
- Respaldos de base de datos y storage programados
- Espacio libre en disco revisado para logs, storage y exportaciones

## Durante el despliegue

- composer install --no-dev --optimize-autoloader
- npm install
- npm run build
- php artisan migrate --force
- php artisan optimize:clear
- php artisan config:cache
- php artisan route:cache
- php artisan view:cache
- php artisan event:cache

## Antes de abrir trafico

- composer health
- Validar respuesta 200 en /up
- Validar login con un usuario real
- Validar dashboard y modulo de inventario
- Validar queue workers en ejecucion
- Validar que no existan fallos nuevos en failed_jobs
- Ejecutar php artisan queue:restart despues del despliegue

## Criterios de salida

- Sin errores 500 en login, dashboard, inventario y reporteria
- Workers activos y consumiendo cola
- Base de datos migrada sin pendientes
- Logs sin errores criticos repetitivos
