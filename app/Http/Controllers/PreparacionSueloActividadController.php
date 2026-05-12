<?php

namespace App\Http\Controllers;

use App\Models\PreparacionSueloActividad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PreparacionSueloActividadController extends Controller
{
	public function index(): View
	{
		$titulo = 'Actividades de Preparación de Suelo';
		$actividades = PreparacionSueloActividad::query()
			->orderByDesc('id')
			->get();
		$actividadesEliminadas = PreparacionSueloActividad::onlyTrashed()
			->orderByDesc('deleted_at')
			->get();

		return view('modules.preparacion_suelo_actividades.index', compact('titulo', 'actividades', 'actividadesEliminadas'));
	}

	public function create(): View
	{
		return view('modules.preparacion_suelo_actividades.create');
	}

	public function store(Request $request): JsonResponse|RedirectResponse
	{
		$validated = $request->validate([
			'nombre' => 'required|string|max:60',
			'actividad_secundaria' => 'required|string|max:60',
			'unidad_medida' => 'required|string|max:50',
			'observaciones' => 'nullable|string|max:150',
			'estado' => 'nullable|in:0,1',
		]);

		$actividad = PreparacionSueloActividad::create([
			'empresa_id' => Auth::user()?->sucursal?->empresa_id ?? Auth::user()?->empresa_id,
			'codigo' => $this->generarCodigo($validated['nombre'], $validated['actividad_secundaria']),
			'nombre' => $validated['nombre'],
			'actividad_secundaria' => $validated['actividad_secundaria'],
			'unidad_medida' => $validated['unidad_medida'],
			'observaciones' => $validated['observaciones'] ?? null,
			'estado' => (int) ($validated['estado'] ?? 1),
			'created_by' => Auth::id(),
		]);

		if ($request->expectsJson()) {
			return response()->json([
				'success' => 'Actividad registrada correctamente.',
				'id' => $actividad->id,
			]);
		}

		return redirect()->route('preparacion-suelo-actividades.index')->with('success', 'Actividad registrada correctamente.');
	}

	public function edit(PreparacionSueloActividad $actividad): View
	{
		return view('modules.preparacion_suelo_actividades.edit', compact('actividad'));
	}

	public function update(Request $request, PreparacionSueloActividad $actividad): JsonResponse|RedirectResponse
	{
		$validated = $request->validate([
			'codigo' => 'required|string|max:20|unique:preparacion_suelo_actividades,codigo,' . $actividad->id,
			'nombre' => 'required|string|max:60',
			'actividad_secundaria' => 'required|string|max:60',
			'unidad_medida' => 'required|string|max:50',
			'observaciones' => 'nullable|string|max:150',
			'estado' => 'nullable|in:0,1',
		]);

		$actividad->update([
			'codigo' => $validated['codigo'],
			'nombre' => $validated['nombre'],
			'actividad_secundaria' => $validated['actividad_secundaria'],
			'unidad_medida' => $validated['unidad_medida'],
			'observaciones' => $validated['observaciones'] ?? null,
			'estado' => (int) ($validated['estado'] ?? 1),
			'updated_by' => Auth::id(),
		]);

		if ($request->expectsJson()) {
			return response()->json(['success' => 'Actividad actualizada correctamente.']);
		}

		return redirect()->route('preparacion-suelo-actividades.index')->with('success', 'Actividad actualizada correctamente.');
	}

	public function destroy(Request $request, PreparacionSueloActividad $actividad): JsonResponse|RedirectResponse
	{
		$actividad->delete();

		if ($request->expectsJson()) {
			return response()->json(['success' => 'Actividad eliminada correctamente.']);
		}

		return redirect()->route('preparacion-suelo-actividades.index')->with('success', 'Actividad eliminada correctamente.');
	}

	public function restore(Request $request, int $actividad): JsonResponse|RedirectResponse
	{
		$actividadRestaurada = PreparacionSueloActividad::onlyTrashed()->findOrFail($actividad);
		$actividadRestaurada->restore();

		if ($request->expectsJson()) {
			return response()->json(['success' => 'Actividad restaurada correctamente.']);
		}

		return redirect()->route('preparacion-suelo-actividades.index')->with('success', 'Actividad restaurada correctamente.');
	}

	private function generarCodigo(string $nombre, string $actividadSecundaria): string
	{
		$iniciales = function (string $texto): string {
			return collect(preg_split('/\s+/', trim($texto)) ?: [])
				->filter()
				->map(fn (string $palabra) => strtoupper(substr($palabra, 0, 1)))
				->implode('');
		};

		$base = $iniciales($nombre) . '-' . $iniciales($actividadSecundaria);
		$codigo = $base;
		$contador = 1;

		while (PreparacionSueloActividad::withTrashed()->where('codigo', $codigo)->exists()) {
			$codigo = $base . $contador;
			$contador++;
		}

		return $codigo;
	}
}