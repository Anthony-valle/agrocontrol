# Smoke Test Post-Deploy

## Aplicacion

- Abrir /up y confirmar respuesta saludable
- Abrir login y autenticar usuario valido
- Confirmar carga del dashboard principal

## Flujos criticos

- Registrar una entrada simple de inventario
- Abrir el reporte de inventario y confirmar que lista registros
- Abrir el reporte de cultivos y confirmar que pagina correctamente
- Ejecutar una importacion de entrada inicial en archivo pequeno y confirmar que entra a cola o termina sin error

## Operacion

- Revisar php artisan about
- Revisar php artisan migrate:status
- Revisar numero de jobs fallidos
- Confirmar queue workers activos
- Confirmar escritura de logs en storage/logs

## Si algo falla

- No abras trafico completo
- Revisa logs en storage/logs/laravel.log
- Revisa failed_jobs y estado de workers
- Repite composer health y /up