<?php

namespace App\Http\Controllers;

use App\Models\Consumo;
use App\Models\Consumo_detalles;
use App\Models\Cultivo;
use App\Models\Lote;
use App\Models\Notificaciones;
use App\Models\PreparacionSueloActividad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PreparacionSueloController extends Controller
{
	private const CATEGORIA = 'Preparacion de Suelo';
	private const OBS_SEPARATOR = ' || OBS: ';

	public function index(): View
	{
		$lotes = Lote::query()->orderBy('nombre')->get(['id', 'nombre']);
		$cultivos = Cultivo::query()
			->orderBy('nombre')
			->get(['id', 'nombre', 'hectareas', 'estado', 'lotes_id', 'unidad_medida']);
		$actividadesCatalogo = $this->obtenerActividadesCatalogo();

		$registrosPaginados = $this->consultaPreparacionSuelo()
			->paginate(15)
			->withQueryString();

		$registros = $registrosPaginados->through(fn (Consumo $consumo) => $this->transformarRegistro($consumo));

		$registrosResumen = $this->consultaPreparacionSuelo()
			->get()
			->map(fn (Consumo $consumo) => $this->transformarRegistro($consumo))
			->values();

		return view('modules.preparacion_suelo.index', compact(
			'lotes',
			'cultivos',
			'actividadesCatalogo',
			'registros',
			'registrosResumen'
		));
	}

	public function store(Request $request): RedirectResponse
	{
		$validated = $request->validate([
			'lote_id' => 'required|integer|exists:lotes,id',
			'cultivo_ids' => 'required|array|min:1',
			'cultivo_ids.*' => 'integer|exists:cultivos,id',
			'fecha' => 'required|date',
			'actividad' => 'required|string|max:255',
			'unidad_medida' => 'required|string|max:100',
			'costo_unitario' => 'required|numeric|min:0.01',
			'observacion' => 'nullable|string|max:255',
		]);

		$cultivos = Cultivo::query()
			->whereIn('id', $validated['cultivo_ids'])
			->get();

		if ($cultivos->count() !== count($validated['cultivo_ids'])) {
			return back()->withInput()->withErrors([
				'cultivo_ids' => 'Uno o más cultivos seleccionados ya no existen.',
			]);
		}

		$cultivosInvalidos = $cultivos->filter(function (Cultivo $cultivo) use ($validated) {
			return (string) $cultivo->lotes_id !== (string) $validated['lote_id']
				|| strcasecmp(trim((string) $cultivo->estado), 'Cerrado') === 0;
		});

		if ($cultivosInvalidos->isNotEmpty()) {
			return back()->withInput()->withErrors([
				'cultivo_ids' => 'Todos los cultivos deben pertenecer al lote seleccionado y estar disponibles.',
			]);
		}

		DB::transaction(function () use ($cultivos, $validated) {
			foreach ($cultivos as $cultivo) {
				$hectareas = (float) ($cultivo->hectareas ?? 0);
				$costoUnitario = (float) $validated['costo_unitario'];
				$subtotal = round($hectareas * $costoUnitario, 2);

				$consumo = Consumo::create($this->filtrarColumnasPersistidas('consumos', [
					'empresa_id' => $cultivo->empresa_id,
					'cultivo_id' => $cultivo->id,
					'fecha_consumo' => $validated['fecha'],
					'total' => $subtotal,
					'estado' => 'PENDIENTE',
					'created_by' => Auth::id(),
				]));

				Consumo_detalles::create($this->filtrarColumnasPersistidas('consumo_detalles', [
					'consumo_id' => $consumo->id,
					'categoria' => self::CATEGORIA,
					'descripcion' => $this->serializarDescripcion($validated['actividad'], $validated['observacion'] ?? null),
					'cantidad' => $hectareas,
					'unidad_medida' => $validated['unidad_medida'],
					'costo_unitario' => $costoUnitario,
					'subtotal' => $subtotal,
					'created_by' => Auth::id(),
				]));

				$this->crearNotificacionMecanizacion(
					$cultivo,
					'Se registró una mecanización para el cultivo ' . $cultivo->nombre
				);
			}
		});

		return redirect()->route('preparacion-suelo.index')->with('success', 'Preparación de suelo registrada correctamente.');
	}

	public function show(Consumo $consumo): View
	{
		$consumo = $this->cargarRegistroPreparacion($consumo);
		$detalle = $this->obtenerDetallePreparacion($consumo);
		$descripcion = $this->parsearDescripcion($detalle?->descripcion);

		return view('modules.preparacion_suelo.show', [
			'consumo' => $consumo,
			'detalle' => $detalle,
			'actividad' => $descripcion['actividad'],
			'observacion' => $descripcion['observacion'],
		]);
	}

	public function edit(Consumo $consumo): View
	{
		$consumo = $this->cargarRegistroPreparacion($consumo);
		$detalle = $this->obtenerDetallePreparacion($consumo);
		$descripcion = $this->parsearDescripcion($detalle?->descripcion);

		return view('modules.preparacion_suelo.edit', [
			'consumo' => $consumo,
			'detalle' => $detalle,
			'actividadesCatalogo' => $this->obtenerActividadesCatalogo(),
			'actividadActual' => $descripcion['actividad'],
			'observacionActual' => $descripcion['observacion'],
		]);
	}

	public function update(Request $request, Consumo $consumo): RedirectResponse
	{
		$consumo = $this->cargarRegistroPreparacion($consumo);

		if (strcasecmp((string) $consumo->estado, 'ANULADO') === 0) {
			return redirect()->route('preparacion-suelo.show', $consumo)->with('error', 'No puedes editar un registro anulado.');
		}

		$validated = $request->validate([
			'fecha' => 'required|date',
			'actividad' => 'required|string|max:255',
			'unidad_medida' => 'required|string|max:100',
			'costo_unitario' => 'required|numeric|min:0.01',
			'observacion' => 'nullable|string|max:255',
		]);

		$detalle = $this->obtenerDetallePreparacion($consumo);
		$hectareas = (float) ($consumo->cultivo?->hectareas ?? $detalle?->cantidad ?? 0);
		$costoUnitario = (float) $validated['costo_unitario'];
		$subtotal = round($hectareas * $costoUnitario, 2);

		DB::transaction(function () use ($consumo, $detalle, $validated, $hectareas, $costoUnitario, $subtotal) {
			$consumo->update($this->filtrarColumnasPersistidas('consumos', [
				'fecha_consumo' => $validated['fecha'],
				'total' => $subtotal,
				'updated_by' => Auth::id(),
			]));

			if ($detalle) {
				$detalle->update($this->filtrarColumnasPersistidas('consumo_detalles', [
					'descripcion' => $this->serializarDescripcion($validated['actividad'], $validated['observacion'] ?? null),
					'cantidad' => $hectareas,
					'unidad_medida' => $validated['unidad_medida'],
					'costo_unitario' => $costoUnitario,
					'subtotal' => $subtotal,
					'updated_by' => Auth::id(),
				]));
			}

			$this->crearNotificacionMecanizacion(
				$consumo->cultivo,
				'Se actualizó una mecanización para el cultivo ' . ($consumo->cultivo->nombre ?? 'N/D')
			);
		});

		return redirect()->route('preparacion-suelo.show', $consumo)->with('success', 'Registro actualizado correctamente.');
	}

	public function destroy(Request $request, Consumo $consumo): JsonResponse|RedirectResponse
	{
		$consumo = $this->cargarRegistroPreparacion($consumo);

		if (strcasecmp((string) $consumo->estado, 'ANULADO') === 0) {
			$message = 'El registro ya estaba anulado.';

			if ($request->expectsJson()) {
				return response()->json(['success' => $message]);
			}

			return redirect()->route('preparacion-suelo.index')->with('success', $message);
		}

		$validated = $request->validate([
			'motivo_anulacion' => 'required|string|max:255',
		]);

		$consumo->update($this->filtrarColumnasPersistidas('consumos', [
			'estado' => 'ANULADO',
			'anulado_by' => Auth::id(),
			'fecha_anulacion' => now(),
			'motivo_anulacion' => $validated['motivo_anulacion'],
		]));

		$this->crearNotificacionMecanizacion(
			$consumo->cultivo,
			'Se anuló una mecanización para el cultivo ' . ($consumo->cultivo->nombre ?? 'N/D')
		);

		if ($request->expectsJson()) {
			return response()->json(['success' => 'Registro anulado correctamente.']);
		}

		return redirect()->route('preparacion-suelo.index')->with('success', 'Registro anulado correctamente.');
	}

	private function consultaPreparacionSuelo()
	{
		return Consumo::query()
			->with([
				'cultivo.lote:id,nombre',
				'creador:id,usuario,name',
				'detalles',
			])
			->whereHas('detalles', function ($query) {
				$query->where('categoria', self::CATEGORIA);
			})
			->orderByDesc('fecha_consumo')
			->orderByDesc('id');
	}

	private function cargarRegistroPreparacion(Consumo $consumo): Consumo
	{
		$consumo->loadMissing([
			'cultivo.lote',
			'creador',
			'detalles',
		]);

		abort_unless($this->obtenerDetallePreparacion($consumo) !== null, 404);

		return $consumo;
	}

	private function obtenerDetallePreparacion(Consumo $consumo): ?Consumo_detalles
	{
		$detalles = $consumo->relationLoaded('detalles') ? $consumo->detalles : $consumo->detalles()->get();

		return $detalles->firstWhere('categoria', self::CATEGORIA);
	}

	private function obtenerActividadesCatalogo(): Collection
	{
		return PreparacionSueloActividad::query()
			->where('estado', 1)
			->orderBy('nombre')
			->orderBy('actividad_secundaria')
			->get()
			->map(function (PreparacionSueloActividad $actividad) {
			return [
				'id' => $actividad->id,
				'codigo' => $actividad->codigo,
				'nombre' => $actividad->nombre,
				'actividad' => $actividad->actividad_secundaria,
				'nombre_completo' => trim($actividad->nombre . ' - ' . $actividad->actividad_secundaria),
				'unidad_medida' => $actividad->unidad_medida ?: 'Servicio',
				'observaciones' => (string) ($actividad->observaciones ?? ''),
				'estado' => (int) $actividad->estado,
			];
		})->values();
	}

	private function transformarRegistro(Consumo $consumo): array
	{
		$detalle = $this->obtenerDetallePreparacion($consumo);
		$descripcion = $this->parsearDescripcion($detalle?->descripcion);

		return [
			'id' => $consumo->id,
			'fecha' => (string) $consumo->fecha_consumo,
			'lote_id' => $consumo->cultivo?->lotes_id,
			'cultivo_id' => $consumo->cultivo_id,
			'lote' => $consumo->cultivo?->lote?->nombre ?? '-',
			'cultivo' => $consumo->cultivo?->nombre ?? '-',
			'actividad' => $descripcion['actividad'] !== '' ? $descripcion['actividad'] : ((string) ($detalle?->descripcion ?? '-')),
			'hectareas' => (float) ($detalle?->cantidad ?? 0),
			'costo_unitario' => (float) ($detalle?->costo_unitario ?? 0),
			'observacion' => $descripcion['observacion'],
			'unidad' => $detalle?->unidad_medida ?? 'Servicio',
			'costo' => (float) ($detalle?->subtotal ?? $consumo->total ?? 0),
			'registrado_por' => $consumo->creador?->usuario ?? $consumo->creador?->name ?? 'Sistema',
			'estado' => (string) $consumo->estado,
		];
	}

	private function serializarDescripcion(string $actividad, ?string $observacion): string
	{
		$actividad = trim($actividad);
		$observacion = trim((string) $observacion);

		if ($observacion === '') {
			return $actividad;
		}

		return $actividad . self::OBS_SEPARATOR . $observacion;
	}

	private function parsearDescripcion(?string $descripcion): array
	{
		$descripcion = trim((string) $descripcion);

		if ($descripcion === '') {
			return ['actividad' => '', 'observacion' => ''];
		}

		$partes = explode(self::OBS_SEPARATOR, $descripcion, 2);

		return [
			'actividad' => trim($partes[0] ?? ''),
			'observacion' => trim($partes[1] ?? ''),
		];
	}

	private function filtrarColumnasPersistidas(string $table, array $payload): array
	{
		return array_intersect_key($payload, array_flip(Schema::getColumnListing($table)));
	}

	private function crearNotificacionMecanizacion(?Cultivo $cultivo, string $mensaje): void
	{
		if (!$cultivo) {
			return;
		}

		Notificaciones::registrarParaSupervision($this->filtrarColumnasPersistidas('notificaciones', [
			'empresa_id' => $cultivo->empresa_id,
			'cultivo_id' => $cultivo->id,
			'user_id' => Auth::id(),
			'mensaje' => $mensaje,
			'tipo' => 'mecanizacion',
			'leido' => false,
		]));
	}
}