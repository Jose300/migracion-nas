<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Models\Conexion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConexionController extends Controller
{
    /**
     * Display a listing of system user connections and sessions.
     */
    public function index(Request $request)
    {
        $query = Conexion::with(['user.roles'])->latest('login_at');

        // Filtros
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('pais')) {
            $query->where('pais', 'LIKE', "%{$request->pais}%");
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('login_at', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('login_at', '<=', $request->fecha_hasta);
        }

        if ($request->filled('buscar')) {
            $buscar = trim($request->buscar);
            $query->where(function ($q) use ($buscar) {
                $q->where('ip_address', 'LIKE', "%{$buscar}%")
                  ->orWhere('dispositivo', 'LIKE', "%{$buscar}%")
                  ->orWhere('pais', 'LIKE', "%{$buscar}%")
                  ->orWhere('ciudad', 'LIKE', "%{$buscar}%")
                  ->orWhereHas('user', function ($uq) use ($buscar) {
                      $uq->where('name', 'LIKE', "%{$buscar}%")
                         ->orWhere('email', 'LIKE', "%{$buscar}%");
                  });
            });
        }

        $conexiones = $query->limit(500)->get();

        // Métricas y Estadísticas
        $totalConexiones = Conexion::count();
        $conexionesActivas = Conexion::where('status', 'Activa')->count();
        $usuariosConectadosHoy = Conexion::whereDate('login_at', Carbon::today())
            ->distinct('user_id')
            ->count('user_id');

        $promedioSegundos = Conexion::where('duracion_segundos', '>', 0)
            ->avg('duracion_segundos') ?? 0;
        $promedioMinutos = round($promedioSegundos / 60, 1);

        $usuarios = User::orderBy('name')->get();
        $paises = Conexion::select('pais')->distinct()->pluck('pais')->filter();

        return view('config.conexiones.index', compact(
            'conexiones',
            'totalConexiones',
            'conexionesActivas',
            'usuariosConectadosHoy',
            'promedioMinutos',
            'usuarios',
            'paises'
        ));
    }

    /**
     * Show connection details in JSON for modal inspection.
     */
    public function show(Conexion $conexion)
    {
        $conexion->load(['user.roles']);

        return response()->json([
            'success' => true,
            'conexion' => $conexion,
            'duracion_formateada' => $conexion->duracion_formateada,
            'bandera_emoji' => $conexion->bandera_emoji,
            'bandera_url' => $conexion->bandera_url,
            'usuario_nombre' => $conexion->user?->nombre_corto ?? 'Usuario Eliminado',
            'usuario_nombre_completo' => $conexion->user?->name ?? 'Usuario Eliminado',
            'usuario_iniciales' => $conexion->user?->iniciales ?? '??',
            'usuario_email' => $conexion->user?->email ?? '-',
            'usuario_rol' => $conexion->user?->roles->pluck('name')->first() ?? 'Sin Rol',
            'login_formateado' => $conexion->login_at ? $conexion->login_at->timezone('America/Caracas')->format('d/m/Y h:i:s A') : '-',
            'actividad_formateada' => $conexion->last_activity_at ? $conexion->last_activity_at->timezone('America/Caracas')->format('d/m/Y h:i:s A') : '-',
            'logout_formateado' => $conexion->logout_at ? $conexion->logout_at->timezone('America/Caracas')->format('d/m/Y h:i:s A') : ($conexion->status === 'Activa' ? 'En Línea Actualmente' : '-'),
        ]);
    }

    /**
     * Cerrar remotamente una sesión activa (por Administrador o Develop).
     */
    public function cerrarSesionRemota(Request $request, Conexion $conexion)
    {
        if (!Auth::user()->can('gestionar-conexiones')) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para cerrar sesiones remotas de usuarios.',
            ], 403);
        }

        // 1. Eliminar sesión de la tabla sessions de Laravel
        try {
            if ($conexion->session_id) {
                DB::table('sessions')->where('id', $conexion->session_id)->delete();
            }
            if ($conexion->user_id) {
                DB::table('sessions')->where('user_id', $conexion->user_id)->delete();
            }
        } catch (\Throwable $e) {}

        // 2. Actualizar registro de la conexión
        $now = Carbon::now();
        $duracion = max(0, $now->diffInSeconds($conexion->login_at));

        $adminName = Auth::user()->name;

        $conexion->update([
            'status' => 'Forzada',
            'logout_at' => $now,
            'duracion_segundos' => $duracion,
            'origen_cierre' => "Cierre remoto forzado por {$adminName}",
        ]);

        \App\Services\AuditService::log([
            'modulo' => 'Conexiones',
            'tabla' => 'conexiones',
            'accion' => 'ACTUALIZAR',
            'registro_id' => $conexion->id,
            'registro_codigo' => (string) $conexion->id,
            'registro_nombre' => "Sesión forzada de {$conexion->user?->name}",
            'data_anterior' => ['status' => 'Activa'],
            'data_nueva' => ['status' => 'Forzada', 'origen_cierre' => "Cierre remoto forzado por {$adminName}"],
            'campos_modificados' => ['status', 'logout_at', 'duracion_segundos', 'origen_cierre'],
        ]);

        return response()->json([
            'success' => true,
            'message' => "La sesión del usuario '{$conexion->user?->name}' ha sido cerrada exitosamente.",
        ]);
    }

    /**
     * Eliminar registro de conexión.
     */
    public function destroy(Conexion $conexion)
    {
        if (!Auth::user()->can('eliminar-conexiones')) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para eliminar registros de conexiones.',
            ], 403);
        }

        \App\Services\AuditService::log([
            'modulo' => 'Conexiones',
            'tabla' => 'conexiones',
            'accion' => 'ELIMINAR',
            'registro_id' => $conexion->id,
            'registro_codigo' => (string) $conexion->id,
            'registro_nombre' => "Conexión #{$conexion->id} ({$conexion->user?->name})",
            'data_anterior' => $conexion->toArray(),
            'data_nueva' => null,
            'campos_modificados' => array_keys($conexion->toArray()),
        ]);

        $conexion->delete();

        return response()->json([
            'success' => true,
            'message' => 'El registro de conexión ha sido eliminado del historial.',
        ]);
    }

    /**
     * Limpiar registros antiguos de conexiones (> 30, > 60, etc. días).
     */
    public function limpiar(Request $request)
    {
        if (!Auth::user()->can('eliminar-conexiones')) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para depurar registros.',
            ], 403);
        }

        $dias = (int) $request->input('dias', 30);
        $fechaLimite = Carbon::now()->subDays($dias);

        $eliminados = Conexion::where('status', '!=', 'Activa')
            ->where('created_at', '<', $fechaLimite)
            ->delete();

        \App\Services\AuditService::log([
            'modulo' => 'Conexiones',
            'tabla' => 'conexiones',
            'accion' => 'ELIMINAR',
            'registro_id' => 0,
            'registro_codigo' => 'DEPURACION',
            'registro_nombre' => "Depuración de {$eliminados} registros de conexiones anteriores a {$dias} días",
            'data_anterior' => ['dias' => $dias, 'fecha_limite' => $fechaLimite->toDateTimeString()],
            'data_nueva' => ['eliminados' => $eliminados],
            'campos_modificados' => ['status', 'created_at'],
        ]);

        return response()->json([
            'success' => true,
            'message' => "Se han depurado {$eliminados} registros de conexiones anteriores a {$dias} días.",
        ]);
    }

    /**
     * Exportar reporte de conexiones a formato estructurado Excel / CSV.
     */
    public function exportExcel(Request $request)
    {
        if (!Auth::user()->can('descargar-excel-conexiones')) {
            abort(403, 'No tiene permiso para descargar el registro de conexiones en Excel.');
        }

        $query = Conexion::with(['user.roles'])->latest('login_at');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('pais')) {
            $query->where('pais', 'LIKE', "%{$request->pais}%");
        }
        if ($request->filled('fecha_desde')) {
            $query->whereDate('login_at', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('login_at', '<=', $request->fecha_hasta);
        }
        if ($request->filled('buscar')) {
            $buscar = trim($request->buscar);
            $query->where(function ($q) use ($buscar) {
                $q->where('ip_address', 'LIKE', "%{$buscar}%")
                  ->orWhere('dispositivo', 'LIKE', "%{$buscar}%")
                  ->orWhere('pais', 'LIKE', "%{$buscar}%")
                  ->orWhere('ciudad', 'LIKE', "%{$buscar}%")
                  ->orWhereHas('user', function ($uq) use ($buscar) {
                      $uq->where('name', 'LIKE', "%{$buscar}%")
                         ->orWhere('email', 'LIKE', "%{$buscar}%");
                  });
            });
        }

        $conexiones = $query->limit(3000)->get();

        $headers = [
            'ID',
            'USUARIO',
            'CORREO ELECTRÓNICO',
            'ROL PRINCIPAL',
            'DIRECCIÓN IP',
            'PAÍS',
            'CIUDAD / REGIÓN',
            'CÓDIGO PAÍS',
            'DISPOSITIVO Y NAVEGADOR',
            'FECHA Y HORA DE INICIO',
            'ÚLTIMA ACTIVIDAD',
            'FECHA DE CIERRE',
            'DURACIÓN (SEGUNDOS)',
            'DURACIÓN FORMATEADA',
            'ESTADO DE LA SESIÓN',
            'MOTIVO O ORIGEN DE CIERRE',
            'ID DE SESIÓN (TOKEN PARCIAL)',
            'USER AGENT COMPLETO',
        ];

        $rows = $conexiones->map(function ($con) {
            return [
                $con->id,
                $con->user?->name ?? 'Usuario Eliminado',
                $con->user?->email ?? '-',
                $con->user?->roles->pluck('name')->first() ?? 'Sin Rol',
                $con->ip_address,
                $con->pais,
                $con->ciudad,
                $con->codigo_pais,
                $con->dispositivo,
                $con->login_at ? $con->login_at->timezone('America/Caracas')->format('d/m/Y h:i:s A') : '',
                $con->last_activity_at ? $con->last_activity_at->timezone('America/Caracas')->format('d/m/Y h:i:s A') : '',
                $con->logout_at ? $con->logout_at->timezone('America/Caracas')->format('d/m/Y h:i:s A') : ($con->status === 'Activa' ? 'En Línea' : ''),
                $con->duracion_segundos,
                $con->duracion_formateada,
                $con->status,
                $con->origen_cierre ?? ($con->status === 'Activa' ? 'Sesión En Progreso' : ''),
                substr((string) $con->session_id, 0, 16) . '...',
                $con->user_agent,
            ];
        });

        $fileName = 'conexiones_sistema_' . Carbon::now()->format('Y-m-d_H-i-s') . '.csv';

        \App\Services\AuditService::log([
            'modulo' => 'Conexiones',
            'tabla' => 'conexiones',
            'accion' => 'EXPORTAR',
            'registro_id' => 0,
            'registro_codigo' => 'EXPORT_EXCEL',
            'registro_nombre' => "Exportación de reporte de conexiones a Excel/CSV ({$conexiones->count()} registros)",
            'data_anterior' => null,
            'data_nueva' => ['total_registros' => $conexiones->count(), 'archivo' => $fileName],
            'campos_modificados' => [],
        ]);

        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            // Byte Order Mark (BOM) UTF-8 para apertura directa en Excel sin caracteres rotos
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, $headers, ';');
            foreach ($rows as $row) {
                fputcsv($handle, $row, ';');
            }
            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }
}

