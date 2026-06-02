<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BodegaController;
use App\Http\Controllers\CategoriasController;
use App\Http\Controllers\ConsumoController;
use App\Http\Controllers\CosechaController;
use App\Http\Controllers\CultivoController;
use App\Http\Controllers\Dashboard;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\FacturaInventarioController;
use App\Http\Controllers\InsumosController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\LaboreController;
use App\Http\Controllers\LoteController;
use App\Http\Controllers\MovimientoInventarioController;
use App\Http\Controllers\NotificacionesController;
use App\Http\Controllers\PlanesCultivoController;
use App\Http\Controllers\PreparacionSueloActividadController;
use App\Http\Controllers\PreparacionSueloController;
use App\Http\Middleware\AuditUserAction;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SolicitudCompraController;
use App\Http\Controllers\SucursaleController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ReporteriaLoteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| RUTAS PÚBLICAS
|--------------------------------------------------------------------------
*/
Route::get('/', function() {
    return redirect()->route('login');
});
if (app()->environment('local')) {
    Route::get('/crear-admin', [AuthController::class, 'crearAdmin']);
}
Route::get('/login', [AuthController::class, 'index'])->name('login');
Route::post('/logear', [AuthController::class, 'logear'])->name('logear');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset.form');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');

/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', AuditUserAction::class])->group(function () {
    Route::get('/reporteria/insumos-categoria', [InsumosController::class, 'reporteCategoriaView'])->name('reporteria.insumos_categoria');
    Route::get('/reporteria/insumos-categoria/{categoria}', [InsumosController::class, 'reporteCategoriaDetalle'])->name('reporteria.insumos_categoria.detalle');
        Route::get('/reporteria/insumos-vencimiento', [InsumosController::class, 'reporteVencimiento'])->name('reporteria.insumos_vencimiento');
        Route::get('/reporteria/insumos-stock-bajo', [InsumosController::class, 'reporteStockBajo'])->name('reporteria.insumos_stock_bajo');


    // Reportería de Lote y Cultivos

    Route::get('/reporteria/lote-cultivos', [ReporteriaLoteController::class, 'index'])->name('reporteria.lote_cultivos');
    Route::get('/reporteria/lote-cultivos/{lote}', [ReporteriaLoteController::class, 'show']);
    Route::get('/reporteria/lote-cultivos/{lote}/excel', [ReporteriaLoteController::class, 'exportExcel'])->name('reporteria.lote_cultivos.excel');
    Route::get('/reporteria/lote-cultivos/{lote}/pdf', [ReporteriaLoteController::class, 'exportPdf'])->name('reporteria.lote_cultivos.pdf');

    // Historial de notificaciones
    Route::get('/notificaciones', [NotificacionesController::class, 'index'])->name('notificaciones.index');

    Route::prefix('compras')->name('compras.')->group(function () {
        Route::get('/solicitudes', [SolicitudCompraController::class, 'review'])->name('solicitudes.index');
        Route::get('/solicitudes/nueva', [SolicitudCompraController::class, 'index'])->name('solicitudes.create');
        Route::get('/solicitudes/revision', [SolicitudCompraController::class, 'review'])->name('solicitudes.review');
        Route::post('/solicitudes', [SolicitudCompraController::class, 'store'])->name('solicitudes.store');
        Route::get('/solicitudes/{solicitud}', [SolicitudCompraController::class, 'show'])->name('solicitudes.show');
        Route::get('/solicitudes/{solicitud}/pdf', [SolicitudCompraController::class, 'downloadPdf'])->name('solicitudes.pdf');
        Route::get('/solicitudes/{solicitud}/orden-compra/nueva', [SolicitudCompraController::class, 'createOrderForm'])->name('solicitudes.order.create');
        Route::post('/solicitudes/{solicitud}/orden-compra', [SolicitudCompraController::class, 'storeOrder'])->name('solicitudes.order.store');
        Route::patch('/solicitudes/{solicitud}/aprobar', [SolicitudCompraController::class, 'approve'])->name('solicitudes.approve');
        Route::patch('/solicitudes/{solicitud}/rechazar', [SolicitudCompraController::class, 'reject'])->name('solicitudes.reject');
        Route::patch('/solicitudes/{solicitud}/proceso', [SolicitudCompraController::class, 'markInProgress'])->name('solicitudes.progress');
        Route::get('/ordenes/validacion', [SolicitudCompraController::class, 'validationIndex'])->name('ordenes.validation.index');
        Route::get('/ordenes/{orden}/validacion', [SolicitudCompraController::class, 'validationForm'])->name('ordenes.validation.show');
        Route::get('/ordenes/reporte', [SolicitudCompraController::class, 'orderReport'])->name('ordenes.report');
        Route::get('/ordenes/{orden}', [SolicitudCompraController::class, 'showOrder'])->name('ordenes.show');
        Route::post('/ordenes/{orden}/recepcion', [SolicitudCompraController::class, 'receiveOrder'])->name('ordenes.receive');
        Route::patch('/ordenes/{orden}/aprobar-diferencias', [SolicitudCompraController::class, 'approveOrderDifferences'])->name('ordenes.approve-differences');
    });

    // Dashboard
    Route::get('/home', [Dashboard::class, 'index'])->name('home');

    // EMPRESAS Y SUCURSALES
    Route::resource('empresas', EmpresaController::class)
        ->except(['show'])
        ->middleware('module.access:empresas')
        ->middlewareFor(['destroy'], 'sensitive.actions');
    Route::resource('sucursal', SucursaleController::class)
        ->except(['show'])
        ->middleware('module.access:sucursales')
        ->middlewareFor(['destroy'], 'sensitive.actions');

    // USUARIOS
    Route::resource('usuarios', UserController::class)
        ->except(['show'])
        ->parameters(['usuarios' => 'user'])
        ->middleware('module.access:usuarios')
        ->middlewareFor(['destroy'], 'sensitive.actions');
    Route::post('usuarios/{user}/reset-password', [UserController::class, 'resetPassword'])
        ->middleware('sensitive.actions')
        ->name('usuarios.reset-password');
    Route::post('usuarios/{user}/reveal-temporary-password', [UserController::class, 'revealTemporaryPassword'])
        ->middleware('sensitive.actions')
        ->name('usuarios.reveal-temporary-password');

    // MÓDULOS AGRÍCOLAS
    Route::resource('categorias', CategoriasController::class)
        ->except(['show'])
        ->middleware('module.access:insumos')
        ->middlewareFor(['destroy'], 'sensitive.actions');
    Route::resource('cultivo', CultivoController::class)
        ->middleware('module.access:cultivos')
        ->middlewareFor(['destroy'], 'sensitive.actions');
    Route::patch('/cultivo/{cultivo}/cerrar', [CultivoController::class, 'cerrar'])
        ->middleware('module.access:cultivos')
        ->middleware('sensitive.actions')
        ->name('cultivo.cerrar');
    Route::patch('/cultivo/{cultivo}/reactivar', [CultivoController::class, 'reactivar'])
        ->middleware('module.access:cultivos')
        ->middleware('sensitive.actions')
        ->name('cultivo.reactivar');
    Route::post('/cultivo/importar', [CultivoController::class, 'importar'])
        ->middleware('module.access:cultivos')
        ->middleware('sensitive.actions')
        ->name('cultivo.importar');
    Route::get('/cultivo/importar/template', [CultivoController::class, 'descargarPlantillaImportacion'])
        ->middleware('module.access:cultivos')
        ->middleware('sensitive.actions')
        ->name('cultivo.importar.template');
    Route::resource('labores', LaboreController::class)
        ->except(['show'])
        ->middleware('module.access:labores')
        ->middlewareFor(['destroy'], 'sensitive.actions');
    Route::resource('lotes', LoteController::class)
        ->except(['show'])
        ->middleware('module.access:lotes')
        ->middlewareFor(['destroy'], 'sensitive.actions');
    Route::resource('planes', PlanesCultivoController::class)
        ->middleware('module.access:planes')
        ->middlewareFor(['destroy'], 'sensitive.actions');
    Route::get('/planes/{plan}/excel', [PlanesCultivoController::class, 'exportExcel'])->name('planes.export.excel');
    Route::get('/planes/{plan}/pdf', [PlanesCultivoController::class, 'exportPdf'])->name('planes.export.pdf');
    Route::post('/planes/importar', [PlanesCultivoController::class, 'importar'])
        ->middleware('module.access:planes')
        ->middleware('sensitive.actions')
        ->name('planes.importar');
    Route::get('/planes/importar/template', [PlanesCultivoController::class, 'descargarPlantillaImportacion'])
        ->middleware('module.access:planes')
        ->middleware('sensitive.actions')
        ->name('planes.importar.template');
    Route::resource('preparacion-suelo', PreparacionSueloController::class)
        ->parameters(['preparacion-suelo' => 'consumo'])
        ->except(['create'])
        ->middleware('module.access:labores')
        ->middlewareFor(['destroy'], 'sensitive.actions');
    Route::resource('preparacion-suelo-actividades', PreparacionSueloActividadController::class)
        ->parameters(['preparacion-suelo-actividades' => 'actividad'])
        ->except(['show'])
        ->middleware('module.access:labores')
        ->middlewareFor(['destroy'], 'sensitive.actions');
    Route::post('preparacion-suelo-actividades/{actividad}/restore', [PreparacionSueloActividadController::class, 'restore'])
        ->middleware('sensitive.actions')
        ->name('preparacion-suelo-actividades.restore');
    Route::resource('consumo', ConsumoController::class)
        ->middleware('module.access:consumo')
        ->middlewareFor(['destroy'], 'sensitive.actions');
    Route::post('/consumo/importar', [ConsumoController::class, 'importar'])
        ->middleware('module.access:consumo')
        ->middleware('sensitive.actions')
        ->name('consumo.importar');
    Route::get('/consumo/importar/template', [ConsumoController::class, 'descargarPlantillaImportacion'])
        ->middleware('module.access:consumo')
        ->middleware('sensitive.actions')
        ->name('consumo.importar.template');
    Route::patch('/consumo/{consumo}/finalizar', [ConsumoController::class, 'finalizar'])
        ->middleware('module.access:consumo')
        ->middleware('sensitive.actions')
        ->name('consumo.finalizar');
    Route::patch('/consumo/{consumo}/anular', [ConsumoController::class, 'anular'])
        ->middleware('module.access:consumo')
        ->middleware('sensitive.actions')
        ->name('consumo.anular');
    Route::patch('/consumo/cultivo/{cultivo}/anular-todos', [ConsumoController::class, 'anularPorCultivo'])
        ->middleware('module.access:consumo')
        ->middleware('sensitive.actions')
        ->name('consumo.anular-cultivo');
    Route::get('/planes/descripciones/{categoria}', [PlanesCultivoController::class, 'descripciones'])
        ->middleware('module.access:planes')
        ->name('planes.descripciones');
    Route::get('/api/inventario_bodega/{insumo_id}', [ConsumoController::class, 'getBodegasLotes'])
        ->middleware('module.access:consumo');
    Route::get('/api/cultivo/{cultivo_id}/consumos', [ConsumoController::class, 'getHistorialConsumo'])
        ->middleware('module.access:consumo');

    Route::post('/notificaciones/marcar-leidas', [NotificacionesController::class, 'marcarLeidas'])->name('notificaciones.marcar-leidas');
    Route::post('/alertas/leidas', [Dashboard::class, 'marcarAlertasLeidas'])->name('alertas.leidas');
    Route::post('/notificaciones/leer', [App\Http\Controllers\NotificacionesController::class, 'leer'])->name('notificaciones.leer');

     // Cosechas
    Route::resource('cosecha', CosechaController::class)
        ->except(['show'])
        ->middleware('module.access:cosecha')
        ->middlewareFor(['destroy'], 'sensitive.actions');
    Route::get('/cosecha/facturadas', [CosechaController::class, 'facturadasIndex'])->name('cosecha.facturadas.index');
    Route::get('/cosecha/{cosecha}/facturas', [CosechaController::class, 'facturas'])->name('cosecha.facturas');
    Route::get('/cosecha/{cosecha}/descarte', [CosechaController::class, 'descarte'])->name('cosecha.descarte');
    Route::get('/cosecha/facturas/{factura}/editar', [CosechaController::class, 'editFactura'])->name('cosecha.facturas.edit');
    Route::post('/cosecha/{cosecha}/facturas', [CosechaController::class, 'storeFactura'])
        ->name('cosecha.facturas.store');
    Route::put('/cosecha/facturas/{factura}', [CosechaController::class, 'updateFactura'])->name('cosecha.facturas.update');
    Route::post('/cosecha/{cosecha}/descarte', [CosechaController::class, 'registrarDescarte'])
        ->name('cosecha.descarte.store');
    Route::delete('/cosecha/facturas/{factura}', [CosechaController::class, 'destroyFactura'])
        ->name('cosecha.facturas.destroy');
    Route::get('/cosecha/facturas/{factura}/exportar', [CosechaController::class, 'exportFactura'])->name('cosecha.facturas.export');
    Route::get('/cultivo/unidad/{id}', [CosechaController::class, 'getUnidad']);
    // Insumos
    Route::get('/insumos/importar', [InsumosController::class, 'importar'])
        ->middleware('module.access:insumos')
        ->middleware('sensitive.actions')
        ->name('insumos.importar');
    Route::post('/insumos/importar', [InsumosController::class, 'importarExcel'])
        ->middleware('module.access:insumos')
        ->middleware('sensitive.actions')
        ->name('insumos.importar.store');
    Route::patch('/insumos/{insumo}/estado', [InsumosController::class, 'cambiarEstado'])
        ->middleware('module.access:insumos')
        ->middleware('sensitive.actions')
        ->name('insumos.estado');
    Route::resource('insumos', InsumosController::class)
        ->middleware('module.access:insumos')
        ->middlewareFor(['destroy'], 'sensitive.actions');

    //Moviminetos
    Route::prefix('movimientos')->name('movimientos.')->middleware('auth')->group(function () {
    Route::get('/index', [MovimientoInventarioController::class, 'index'])->name('index');
    Route::get('/lotes-insumo', [MovimientoInventarioController::class, 'getLotesPorInsumo'])->name('lotes-insumo');

    // Entrada
    Route::get('/entradas', [MovimientoInventarioController::class, 'entradaIndex'])->name('entradas.index');
    Route::get('/entrada', [MovimientoInventarioController::class, 'entrada'])->middleware('module.access:entrada')->name('entrada');
    Route::post('/entrada', [MovimientoInventarioController::class, 'entradaStore'])->middleware('module.access:entrada')->name('entrada.store');
    Route::get('/entrada/importar', [MovimientoInventarioController::class, 'entradaImportar'])
        ->middleware('module.access:entrada')
        ->middleware('sensitive.actions')
        ->name('entrada.importar');
    Route::post('/entrada/importar', [MovimientoInventarioController::class, 'entradaImportarStore'])
        ->middleware('module.access:entrada')
        ->middleware('sensitive.actions')
        ->name('entrada.importar.store');
    Route::get('/entrada/importar/template', [MovimientoInventarioController::class, 'descargarPlantillaEntradaInicial'])
        ->middleware('module.access:entrada')
        ->middleware('sensitive.actions')
        ->name('entrada.importar.template');


    // Ajuste
    Route::get('/ajuste', [MovimientoInventarioController::class, 'ajuste'])->middleware('module.access:ajuste')->name('ajuste');
    Route::post('/ajuste', [MovimientoInventarioController::class, 'ajusteStore'])->middleware('module.access:ajuste')->name('ajuste.store');

    // Traslado
    Route::get('/traslado', [MovimientoInventarioController::class, 'traslado'])->middleware('module.access:traslado')->name('traslado');
    Route::post('/traslado', [MovimientoInventarioController::class, 'trasladoStore'])->middleware('module.access:traslado')->name('traslado.store');

    // Salida
    Route::get('/salida', [MovimientoInventarioController::class, 'salida'])->middleware('module.access:inventarios')->name('salida');
    Route::post('/salida', [MovimientoInventarioController::class, 'salidaStore'])->middleware('module.access:inventarios')->name('salida.store');
    });

   
    // Obtener unidad
    
    Route::get('reporte/cultivo/{id}', [ReporteController::class, 'reporteFinal'])->name('reporte.cultivo.final');
    Route::get('reporte/cultivo/{cultivo_id}/plan-real-semanal', [ReporteController::class, 'reportePlanRealSemanal'])->name('reporte.cultivo.plan-real-semanal');
    Route::get('reporte/cultivo/{cultivo_id}/plan-real-semanal/excel', [ReporteController::class, 'reportePlanRealSemanalExcel'])->name('reporte.cultivo.plan-real-semanal.excel');
    Route::get('reporte/cultivo/{id}/categoria-detalle', [ReporteController::class, 'categoriaDetalle'])->name('reporte.cultivo.categoria-detalle');
    Route::get('reporte/cultivo/{cultivo_id}/historial', [ReporteController::class, 'historialConsumo'])->name('reporte.cultivo.historial');
    Route::get('reporte/cultivo/{cultivo_id}/historial/consumo/{consumo_id}', [ReporteController::class, 'historialConsumoDetalle'])->name('reporte.cultivo.historial.detalle');
    Route::get('reporte/cultivo/{cultivo_id}/historial/excel', [ReporteController::class, 'historialConsumoExcel'])->name('reporte.cultivo.historial.excel');
    Route::get('reporte/cultivo/{cultivo_id}/historial/pdf', [ReporteController::class, 'historialConsumoPdf'])->name('reporte.cultivo.historial.pdf');
    // INVENTARIOS
    Route::resource('inventarios', InventarioController::class)->only(['index'])->middleware('module.access:inventarios');
    Route::get('inventarios/{id}/detalle', [InventarioController::class, 'detalle'])->middleware('module.access:inventarios')->name('inventarios.detalle');

    // BODEGAS
    Route::resource('bodegas', BodegaController::class)
        ->except(['show'])
        ->middleware('module.access:bodegas')
        ->middlewareFor(['destroy'], 'sensitive.actions');

    // ROLES
    Route::resource('rol', RoleController::class)
        ->middleware('module.access:roles')
        ->except(['show'])
        ->middlewareFor(['destroy'], 'sensitive.actions');

    // Dashboard de reportería general
    Route::get('/reporteria/dashboard', [\App\Http\Controllers\Reporteria\DashboardReportController::class, 'index'])->name('reporteria.dashboard');
    // Reporterías de lotes
    Route::get('/reporteria/lotes', [\App\Http\Controllers\Reporteria\LotesReportController::class, 'index'])->name('reporteria.lotes');
    Route::get('/reporteria/lotes/excel', [\App\Http\Controllers\Reporteria\LotesReportController::class, 'exportExcel'])->name('reporteria.lotes.excel');
    Route::get('/reporteria/lotes/pdf', [\App\Http\Controllers\Reporteria\LotesReportController::class, 'exportPdf'])->name('reporteria.lotes.pdf');
    Route::get('/reporteria/lotes/{lote}', [\App\Http\Controllers\Reporteria\LotesReportController::class, 'show'])->name('reporteria.lotes.show');
    Route::get('/reporteria/lotes/{lote}/excel', [\App\Http\Controllers\Reporteria\LotesReportController::class, 'exportDetalleExcel'])->name('reporteria.lotes.show.excel');
    Route::get('/reporteria/lotes/{lote}/pdf', [\App\Http\Controllers\Reporteria\LotesReportController::class, 'exportDetallePdf'])->name('reporteria.lotes.show.pdf');
    // Reporterías de cultivos
    Route::get('/reporteria/cultivos', [\App\Http\Controllers\Reporteria\CultivosReportController::class, 'index'])->name('reporteria.cultivos');
    Route::get('/reporteria/cultivos/excel', [\App\Http\Controllers\Reporteria\CultivosReportController::class, 'exportExcel'])->name('reporteria.cultivos.excel');
    Route::get('/reporteria/cultivos/pdf', [\App\Http\Controllers\Reporteria\CultivosReportController::class, 'exportPdf'])->name('reporteria.cultivos.pdf');
    Route::get('/reporteria/cultivos/consumos-general', [\App\Http\Controllers\Reporteria\CultivosReportController::class, 'consumosGeneral'])->name('reporteria.cultivos.consumos-general');
    Route::get('/reporteria/cultivos/consumos-general/{cultivo}', [\App\Http\Controllers\Reporteria\CultivosReportController::class, 'consumosGeneralDetalle'])->whereNumber('cultivo')->name('reporteria.cultivos.consumos-general.detalle');
    Route::get('/reporteria/cultivos/consumos-general/excel', [\App\Http\Controllers\Reporteria\CultivosReportController::class, 'exportConsumosGeneralExcel'])->name('reporteria.cultivos.consumos-general.excel');
    Route::get('/reporteria/cultivos/{cultivo}/consumos-fecha', [\App\Http\Controllers\Reporteria\CultivosReportController::class, 'consumosPorFecha'])->name('reporteria.cultivos.consumos-fecha');
    Route::get('/reporteria/cultivos/{cultivo}/consumos-categoria', [\App\Http\Controllers\Reporteria\CultivosReportController::class, 'consumosPorCategoria'])->name('reporteria.cultivos.consumos-categoria');
    Route::get('/reporteria/cultivos/{cultivo}/consumos-categoria/pagina', [\App\Http\Controllers\Reporteria\CultivosReportController::class, 'consumosPorCategoriaPagina'])->name('reporteria.cultivos.consumos-categoria.pagina');
    Route::get('/reporteria/cultivos/{cultivo}/consumos-categoria/excel', [\App\Http\Controllers\Reporteria\CultivosReportController::class, 'exportConsumosCategoriaExcel'])->name('reporteria.cultivos.consumos-categoria.excel');
    Route::get('/reporteria/cultivos/{cultivo}/consumos-categoria/pdf', [\App\Http\Controllers\Reporteria\CultivosReportController::class, 'exportConsumosCategoriaPdf'])->name('reporteria.cultivos.consumos-categoria.pdf');
    Route::get('/reporteria/cultivos/{cultivo}', [\App\Http\Controllers\Reporteria\CultivosReportController::class, 'show'])->name('reporteria.cultivos.show');
    // Reportería de consumos
    Route::get('/reporteria/consumos', [\App\Http\Controllers\Reporteria\ConsumosReportController::class, 'index'])->name('reporteria.consumos');
    Route::get('/reporteria/consumos/excel', [\App\Http\Controllers\Reporteria\ConsumosReportController::class, 'exportExcel'])->name('reporteria.consumos.excel');
    Route::get('/reporteria/consumos/pdf', [\App\Http\Controllers\Reporteria\ConsumosReportController::class, 'exportPdf'])->name('reporteria.consumos.pdf');
    // Reporterías de inventario
    Route::get('/reporteria/inventario', [\App\Http\Controllers\Reporteria\InventarioReportController::class, 'index'])->name('reporteria.inventario');
    Route::get('/reporteria/inventario/excel', [\App\Http\Controllers\Reporteria\InventarioReportController::class, 'exportExcel'])->name('reporteria.inventario.excel');
    Route::get('/reporteria/inventario/pdf', [\App\Http\Controllers\Reporteria\InventarioReportController::class, 'exportPdf'])->name('reporteria.inventario.pdf');
    Route::get('/reporteria/facturas-entradas', [FacturaInventarioController::class, 'index'])->name('reporteria.facturas_entradas');
    Route::get('/reporteria/facturas-entradas/{factura_inventario}', [FacturaInventarioController::class, 'show'])->name('reporteria.facturas_entradas.show');
    Route::get('/reporteria/facturas-entradas/{factura_inventario}/archivo', [FacturaInventarioController::class, 'archivo'])->name('reporteria.facturas_entradas.archivo');
    // Reporterías de cosechas
    Route::get('/reporteria/cosechas', [\App\Http\Controllers\Reporteria\CosechasReportController::class, 'index'])->name('reporteria.cosechas');
    Route::get('/reporteria/cosechas/excel', [\App\Http\Controllers\Reporteria\CosechasReportController::class, 'exportExcel'])->name('reporteria.cosechas.excel');
    Route::get('/reporteria/cosechas/pdf', [\App\Http\Controllers\Reporteria\CosechasReportController::class, 'exportPdf'])->name('reporteria.cosechas.pdf');
    // Reporterías de mano de obra
    Route::get('/reporteria/mano-obra', [\App\Http\Controllers\Reporteria\ManoObraReportController::class, 'index'])->name('reporteria.mano_obra');
    // Reporterías de rentabilidad
    Route::get('/reporteria/rentabilidad', [\App\Http\Controllers\Reporteria\RentabilidadReportController::class, 'index'])->name('reporteria.rentabilidad');
    // Reporterías de alertas y notificaciones
    Route::get('/reporteria/alertas', [\App\Http\Controllers\Reporteria\AlertasReportController::class, 'index'])->name('reporteria.alertas');

    // Soporte y respaldo del sistema
    Route::get('/soporte', [SupportController::class, 'index'])->name('soporte.index');
    Route::post('/soporte/backup', [SupportController::class, 'createBackup'])
        ->middleware('sensitive.actions')
        ->name('soporte.backup.create');
    Route::get('/soporte/backup/{file}', [SupportController::class, 'downloadBackup'])->name('soporte.backup.download');
    Route::get('/soporte/tecnico', [SupportController::class, 'tecnico'])->name('soporte.tecnico.index');
    Route::post('/soporte/tecnico', [SupportController::class, 'storeTechnicalRequest'])->name('soporte.tecnico.store');
    Route::patch('/soporte/tecnico/{ticket}', [SupportController::class, 'updateTechnicalRequest'])
        ->middleware('sensitive.actions')
        ->name('soporte.tecnico.update');
    Route::get('/soporte/recuperar', [SupportController::class, 'recoveryIndex'])->name('soporte.recuperar.index');
    Route::post('/soporte/recuperar/{tipo}/{id}/restaurar', [SupportController::class, 'restoreDeleted'])
        ->middleware('sensitive.actions')
        ->name('soporte.recuperar.restaurar');
});