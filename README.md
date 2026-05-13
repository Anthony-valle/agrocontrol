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

## Produccion

Para un despliegue estable en una empresa grande, no uses la configuracion local tal como viene. Usa una copia basada en .env.production.example y ajusta las variables del servidor.

### Minimos recomendados

- APP_ENV=production
- APP_DEBUG=false
- APP_URL con el dominio real
- SESSION_SECURE_COOKIE=true si usas HTTPS
- CACHE_STORE=redis
- QUEUE_CONNECTION=redis
- SESSION_DRIVER=redis o database
- REDIS_CLIENT=phpredis si la extension esta instalada; si no, REDIS_CLIENT=predis
- LOG_STACK=daily
- LOG_LEVEL=warning o info

### Flujo de despliegue recomendado

1. composer install --no-dev --optimize-autoloader
2. npm install
3. npm run build
4. Configurar .env de produccion
5. php artisan key:generate
6. composer deploy
7. Levantar un worker permanente con php artisan queue:work --queue=default --tries=3 --timeout=120
8. Ejecutar composer health y validar /up antes de abrir trafico

### Redis en produccion

- Este proyecto ya puede usar Redis sin la extension phpredis porque incluye predis/predis.
- Para cargas grandes, phpredis sigue siendo la mejor opcion si el servidor la tiene instalada.
- Si el servidor no tiene la extension, usa REDIS_CLIENT=predis en el .env de produccion.
- Si activas Redis para sesiones, cache y colas, no dejes queue:listen en produccion; usa queue:work supervisado.

### Workers persistentes

- Se incluyen ejemplos listos para adaptar en deploy/supervisor/agrocontrol-worker.conf y deploy/systemd/agrocontrol-queue.service.
- Ajusta las rutas /var/www/agrocontrol, el usuario del proceso y la cantidad de workers segun CPU, RAM y volumen de importaciones.
- Despues de cada despliegue ejecuta php artisan queue:restart para que los workers carguen el nuevo codigo.
- La carga masiva de entrada inicial puede procesarse en segundo plano cuando QUEUE_CONNECTION no es sync, reduciendo timeouts y bloqueos en peticiones web grandes.
- Las notificaciones administrativas de supervision tambien pueden salir del request cuando la cola no usa sync, reduciendo latencia en operaciones CRUD frecuentes.

### Chequeos operativos

- El endpoint de salud esta disponible en /up
- Reinicia workers despues de cada despliegue con php artisan queue:restart
- Usa HTTPS y un servidor web real como Nginx o Apache, no php artisan serve
- Programa respaldos de base de datos y archivos de storage
- Configura Redis si esperas alto volumen o procesos pesados de reporteria/importacion
- Monitoriza colas fallidas, tiempo de ejecucion y crecimiento de tablas jobs, failed_jobs y job_batches

### Checklist final

- Usa el checklist de salida en deploy/checklists/production-go-live.md antes de publicar.
- Usa el smoke test de deploy/checklists/post-deploy-smoke-test.md justo despues del despliegue.
- Ejecuta composer health como verificacion rapida de runtime Laravel.


## Modulos principales

- Consumos
- Inventario
- Cultivos
- Cosechas
- Reporteria
- Notificaciones
