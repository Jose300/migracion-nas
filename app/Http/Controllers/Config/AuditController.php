<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $query = Auditoria::query()->latest('created_at');

        // Global metric counts (independent of current page filter, reflecting system health)
        $totalEventos = Auditoria::count();
        $totalCreaciones = Auditoria::where('accion', 'CREAR')->count();
        $totalModificaciones = Auditoria::whereIn('accion', ['ACTUALIZAR', 'CAMBIO_ESTADO'])->count();
        $totalEliminaciones = Auditoria::where('accion', 'ELIMINAR')->count();

        // Filters
        if ($request->filled('modulo') && $request->modulo !== 'all') {
            $query->where('modulo', $request->modulo);
        }

        if ($request->filled('accion') && $request->accion !== 'all') {
            $query->where('accion', $request->accion);
        }

        if ($request->filled('user_id') && $request->user_id !== 'all') {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('desde')) {
            $query->whereDate('created_at', '>=', $request->desde);
        }

        if ($request->filled('hasta')) {
            $query->whereDate('created_at', '<=', $request->hasta);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('user_name', 'like', "%{$search}%")
                  ->orWhere('registro_nombre', 'like', "%{$search}%")
                  ->orWhere('modulo', 'like', "%{$search}%")
                  ->orWhere('tabla', 'like', "%{$search}%")
                  ->orWhere('accion', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('registro_id', 'like', "%{$search}%");
            });
        }

        $perPage = (int) ($request->get('per_page', 25));
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 25;
        }

        $auditorias = $query->paginate($perPage)->withQueryString();

        // Data for dropdown filters
        $modulos = Auditoria::select('modulo')->distinct()->pluck('modulo')->toArray();
        $defaultModulos = ['Usuarios', 'Roles', 'Permisos', 'Mi Perfil'];
        $modulos = array_values(array_unique(array_merge($defaultModulos, $modulos)));
        sort($modulos);

        $usuarios = User::orderBy('name')->get(['id', 'name']);

        $acciones = [
            'CREAR' => 'Creaciones (INSERT)',
            'ACTUALIZAR' => 'Modificaciones (UPDATE)',
            'CAMBIO_ESTADO' => 'Cambio de Estado',
            'ELIMINAR' => 'Eliminaciones (DELETE)',
        ];

        return view('config.auditoria.index', compact(
            'auditorias',
            'totalEventos',
            'totalCreaciones',
            'totalModificaciones',
            'totalEliminaciones',
            'modulos',
            'usuarios',
            'acciones'
        ));
    }

    public function show(Auditoria $auditoria)
    {
        return response()->json([
            'id' => $auditoria->id,
            'fecha' => $auditoria->created_at->format('d/m/Y'),
            'hora' => $auditoria->created_at->format('h:i:s A'),
            'user_name' => $auditoria->user_name,
            'user_email' => $auditoria->user_email,
            'user_role' => $auditoria->user_role,
            'modulo' => $auditoria->modulo,
            'tabla' => $auditoria->tabla,
            'accion' => $auditoria->accion,
            'badge' => $auditoria->accion_badge,
            'registro_id' => $auditoria->registro_id,
            'registro_codigo' => $auditoria->registro_codigo,
            'registro_nombre' => $auditoria->registro_nombre,
            'data_anterior' => $auditoria->data_anterior,
            'data_nueva' => $auditoria->data_nueva,
            'campos_modificados' => $auditoria->campos_modificados,
            'ip_address' => $auditoria->ip_address,
            'browser' => $auditoria->browser,
            'user_agent' => $auditoria->user_agent,
            'url' => $auditoria->url,
            'metodo_http' => $auditoria->metodo_http,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $query = Auditoria::query()->latest('created_at');

        if ($request->filled('modulo') && $request->modulo !== 'all') {
            $query->where('modulo', $request->modulo);
        }

        if ($request->filled('accion') && $request->accion !== 'all') {
            $query->where('accion', $request->accion);
        }

        if ($request->filled('user_id') && $request->user_id !== 'all') {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('desde')) {
            $query->whereDate('created_at', '>=', $request->desde);
        }

        if ($request->filled('hasta')) {
            $query->whereDate('created_at', '<=', $request->hasta);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('user_name', 'like', "%{$search}%")
                  ->orWhere('registro_nombre', 'like', "%{$search}%")
                  ->orWhere('modulo', 'like', "%{$search}%")
                  ->orWhere('tabla', 'like', "%{$search}%")
                  ->orWhere('accion', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        $records = $query->get();
        $filename = 'auditoria_sistema_' . date('Y_m_d_His') . '.csv';

        $response = new StreamedResponse(function () use ($records) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel recognition of accents and special characters
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'ID',
                'Fecha',
                'Hora',
                'Usuario',
                'Email',
                'Rol',
                'Módulo',
                'Tabla',
                'Acción',
                'ID Registro',
                'Nombre Registro',
                'Campos Modificados',
                'IP Origen',
                'Navegador / SO',
                'URL',
                'Método HTTP',
            ], ';');

            foreach ($records as $row) {
                fputcsv($handle, [
                    $row->id,
                    $row->created_at->format('d/m/Y'),
                    $row->created_at->format('h:i:s A'),
                    $row->user_name,
                    $row->user_email,
                    $row->user_role,
                    $row->modulo,
                    $row->tabla,
                    $row->accion,
                    $row->registro_id,
                    $row->registro_nombre,
                    is_array($row->campos_modificados) ? implode(', ', $row->campos_modificados) : '',
                    $row->ip_address,
                    $row->browser,
                    $row->url,
                    $row->metodo_http,
                ], ';');
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }
}
