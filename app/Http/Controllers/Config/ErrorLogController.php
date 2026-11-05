<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Models\LogError;
use App\Services\ErrorLogService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ErrorLogController extends Controller
{
    public function index(Request $request)
    {
        $query = LogError::query()->latest('created_at');

        // Global metric counts
        $totalErrores = LogError::count();
        $totalPendientes = LogError::where('estado', 'pendiente')->count();
        $totalEnRevision = LogError::where('estado', 'en_revision')->count();
        $totalResueltos = LogError::where('estado', 'resuelto')->count();
        $totalCriticos = LogError::whereIn('nivel', ['CRITICAL', 'EMERGENCY', 'ALERT'])->count();

        // Filters
        if ($request->filled('nivel') && $request->nivel !== 'all') {
            $query->where('nivel', strtoupper($request->nivel));
        }

        if ($request->filled('estado') && $request->estado !== 'all') {
            $query->where('estado', strtolower($request->estado));
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
                $q->where('mensaje', 'like', "%{$search}%")
                  ->orWhere('clase_error', 'like', "%{$search}%")
                  ->orWhere('archivo', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%")
                  ->orWhere('url', 'like', "%{$search}%")
                  ->orWhere('codigo', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        $perPage = (int) ($request->get('per_page', 25));
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 25;
        }

        $errores = $query->paginate($perPage)->withQueryString();

        $niveles = [
            'CRITICAL' => 'Crítico (CRITICAL)',
            'ERROR' => 'Error (ERROR)',
            'WARNING' => 'Advertencia (WARNING)',
            'EMERGENCY' => 'Emergencia (EMERGENCY)',
            'ALERT' => 'Alerta (ALERT)',
            'NOTICE' => 'Aviso (NOTICE)',
        ];

        $estados = [
            'pendiente' => 'Pendiente',
            'en_revision' => 'En Revisión',
            'resuelto' => 'Resuelto',
            'ignorado' => 'Ignorado',
        ];

        return view('config.log-errores.index', compact(
            'errores',
            'totalErrores',
            'totalPendientes',
            'totalEnRevision',
            'totalResueltos',
            'totalCriticos',
            'niveles',
            'estados'
        ));
    }

    public function show(LogError $error)
    {
        $error->load(['user', 'resueltoPor']);

        return response()->json([
            'id' => $error->id,
            'nivel' => $error->nivel,
            'nivel_badge' => $error->nivel_badge,
            'clase_error' => $error->clase_error,
            'mensaje' => $error->mensaje,
            'codigo' => $error->codigo,
            'archivo' => $error->archivo,
            'archivo_corto' => $error->archivo_corto,
            'linea' => $error->linea,
            'trace' => $error->trace,
            'url' => $error->url,
            'metodo_http' => $error->metodo_http,
            'ip_address' => $error->ip_address,
            'user_agent' => $error->user_agent,
            'request_data' => $error->request_data,
            'headers' => $error->headers,
            'user_id' => $error->user_id,
            'user_name' => $error->user_name,
            'user_email' => $error->user_email,
            'estado' => $error->estado,
            'estado_badge' => $error->estado_badge,
            'solucion_notas' => $error->solucion_notas,
            'resuelto_por' => $error->resueltoPor?->name,
            'resuelto_at' => $error->resuelto_at?->format('d/m/Y h:i A'),
            'fecha' => $error->created_at->format('d/m/Y'),
            'hora' => $error->created_at->format('h:i:s A'),
        ]);
    }

    public function updateEstado(Request $request, LogError $error)
    {
        $request->validate([
            'estado' => 'required|in:pendiente,en_revision,resuelto,ignorado',
            'solucion_notas' => 'nullable|string|max:2000',
        ]);

        $error->estado = $request->estado;
        $error->solucion_notas = $request->solucion_notas;

        if ($request->estado === 'resuelto') {
            $error->resuelto_por_user_id = auth()->id();
            $error->resuelto_at = now();
        } else {
            $error->resuelto_por_user_id = null;
            $error->resuelto_at = null;
        }

        $error->save();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Estado del error actualizado correctamente.',
                'estado_badge' => $error->estado_badge,
            ]);
        }

        return redirect()->back()->with('success', 'Estado del error actualizado correctamente.');
    }

    public function destroy(LogError $error)
    {
        $error->delete();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Registro de error eliminado correctamente.'
            ]);
        }

        return redirect()->route('log-errores.index')->with('success', 'Registro de error eliminado correctamente.');
    }

    public function clear(Request $request)
    {
        $mode = $request->input('mode', 'resueltos');

        if ($mode === 'todos') {
            LogError::truncate();
            $msg = 'Se han vaciado todos los registros de errores.';
        } else {
            $count = LogError::where('estado', 'resuelto')->delete();
            $msg = "Se han eliminado {$count} registros de errores resueltos.";
        }

        return redirect()->route('log-errores.index')->with('success', $msg);
    }

    public function simulate()
    {
        try {
            // Throw a deliberate demonstration exception
            throw new \RuntimeException(
                "Excepción simulada de prueba: Verificación del módulo de Log de Errores y disparador automático.",
                500
            );
        } catch (\Throwable $e) {
            ErrorLogService::capture($e, 'CRITICAL', [
                'origen' => 'Simulación de prueba manual',
                'modulo' => 'Log de Errores',
                'trigger_test' => true,
            ]);
        }

        return redirect()->route('log-errores.index')->with('success', '¡Error de prueba simulado y capturado exitosamente en el Log de Errores!');
    }

    public function export(Request $request): StreamedResponse
    {
        $query = LogError::query()->latest('created_at');

        if ($request->filled('nivel') && $request->nivel !== 'all') {
            $query->where('nivel', strtoupper($request->nivel));
        }

        if ($request->filled('estado') && $request->estado !== 'all') {
            $query->where('estado', strtolower($request->estado));
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
                $q->where('mensaje', 'like', "%{$search}%")
                  ->orWhere('clase_error', 'like', "%{$search}%")
                  ->orWhere('archivo', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%")
                  ->orWhere('url', 'like', "%{$search}%")
                  ->orWhere('codigo', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        $records = $query->get();
        $filename = 'log_errores_sistema_' . date('Y_m_d_His') . '.csv';

        $response = new StreamedResponse(function () use ($records) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'ID',
                'Fecha',
                'Hora',
                'Nivel',
                'Estado',
                'Clase Error',
                'Código',
                'Mensaje',
                'Archivo',
                'Línea',
                'Usuario Afectado',
                'Email',
                'IP Origen',
                'Método HTTP',
                'URL',
                'Solución / Notas',
                'Resuelto Por',
                'Resuelto En',
            ], ';');

            foreach ($records as $row) {
                fputcsv($handle, [
                    $row->id,
                    $row->created_at->format('d/m/Y'),
                    $row->created_at->format('h:i:s A'),
                    $row->nivel,
                    $row->estado,
                    $row->clase_error,
                    $row->codigo,
                    $row->mensaje,
                    $row->archivo,
                    $row->linea,
                    $row->user_name,
                    $row->user_email,
                    $row->ip_address,
                    $row->metodo_http,
                    $row->url,
                    $row->solucion_notas,
                    $row->resueltoPor?->name,
                    $row->resuelto_at?->format('d/m/Y H:i:s'),
                ], ';');
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }
}
