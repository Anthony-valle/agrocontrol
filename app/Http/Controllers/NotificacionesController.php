<?php

namespace App\Http\Controllers;

use App\Models\Notificaciones;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificacionesController extends Controller
{


    public function leer()
     {
        if (Auth::check()) {
            Notificaciones::where('user_id', Auth::id())
                ->where('leido', false)
                ->update(['leido' => true]);
        }
        return response()->json(['success' => true]);
    }

    
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $search = trim((string) request('search', ''));
        $perPage = (int) request('per_page', 15);
        $sort = trim((string) request('sort', 'recentes'));
        if (!in_array($perPage, [5, 10, 15, 20, 50], true)) {
            $perPage = 15;
        }

        if (!in_array($sort, ['recentes', 'antiguas'], true)) {
            $sort = 'recentes';
        }

        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        abort_unless($user && ($user->isSuperUser() || $user->hasRole('compra')), 403);

        $query = Notificaciones::with(['usuario', 'cultivo'])
            ->where('user_id', $user->id);

        if ($sort === 'antiguas') {
            $query->orderBy('created_at')->orderBy('id');
        } else {
            $query->orderByDesc('created_at')->orderByDesc('id');
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('mensaje', 'like', '%' . $search . '%')
                    ->orWhere('tipo', 'like', '%' . $search . '%')
                    ->orWhereHas('cultivo', function ($cultivoQuery) use ($search) {
                        $cultivoQuery->where('nombre', 'like', '%' . $search . '%')
                            ->orWhere('codigo', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('usuario', function ($usuarioQuery) use ($search) {
                        $usuarioQuery->where('usuario', 'like', '%' . $search . '%')
                            ->orWhere('name', 'like', '%' . $search . '%')
                            ->orWhere('nombre_completo', 'like', '%' . $search . '%');
                    });
            });
        }

        $notificaciones = $query->paginate($perPage)->withQueryString();

        $resumenTipos = $notificaciones->getCollection()
            ->groupBy(fn ($notificacion) => strtolower((string) $notificacion->tipo))
            ->map(function ($items, $tipo) {
                $label = match ($tipo) {
                    'mecanizacion' => 'Mecanización',
                    'auditoria' => 'Auditoría',
                    default => ucfirst($tipo !== '' ? $tipo : 'general'),
                };

                return [
                    'label' => $label,
                    'total' => $items->count(),
                    'ultima_fecha' => $items->max('created_at'),
                ];
            })
            ->sortByDesc('total')
            ->values();

        return view('modules.notificaciones.index', compact('notificaciones', 'search', 'perPage', 'resumenTipos', 'sort'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Notificaciones $notificaciones)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Notificaciones $notificaciones)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Notificaciones $notificaciones)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Notificaciones $notificaciones)
    {
        //
    }
}
