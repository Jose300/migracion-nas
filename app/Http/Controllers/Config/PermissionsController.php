<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;

class PermissionsController extends Controller
{
    public function index(Request $request)
    {
        $roleOrder = ['Develop', 'Admin', 'Usuario'];
        $roles = Role::all()->sortBy(function ($role) use ($roleOrder) {
            $idx = array_search($role->name, $roleOrder);
            return $idx !== false ? $idx : 99;
        })->values();

        $perPage = (int) $request->get('per_page', 25);
        if ($perPage <= 0) {
            $perPage = 25;
        }

        $query = Permission::query();
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('module', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $permissions = $query->orderBy('module')->orderBy('name')->paginate($perPage)->withQueryString();

        $developRole = $roles->firstWhere('name', 'Develop');
        $developPermissionsCount = $developRole ? $developRole->permissions()->count() : Permission::count();
        $totalPermissionsCount = Permission::count();

        return view('config.permissions.index', compact('roles', 'permissions', 'developPermissionsCount', 'totalPermissionsCount'));
    }

    public function toggle(Request $request)
    {
        try {
            $role = Role::findById($request->role_id);
            $permission = Permission::findById($request->permission_id);

            // Protección permanente para el rol Develop
            if (strtolower($role->name) === 'develop') {
                return response()->json([
                    'success' => false,
                    'message' => 'El rol Develop posee control total permanente y sus permisos no pueden ser modificados.'
                ], 403);
            }

            if ($role->hasPermissionTo($permission)) {
                $role->revokePermissionTo($permission);
                $status = 'revoked';
                $accion = 'ELIMINAR';
            } else {
                $role->givePermissionTo($permission);
                $status = 'assigned';
                $accion = 'CREAR';
            }

            \App\Services\AuditService::log([
                'modulo' => 'Permisos',
                'tabla' => 'role_has_permissions',
                'accion' => $accion,
                'registro_id' => $permission->id,
                'registro_codigo' => $permission->name,
                'registro_nombre' => "Permiso '{$permission->name}' a Rol '{$role->name}'",
                'data_anterior' => ['has_permission' => $status === 'revoked'],
                'data_nueva' => ['has_permission' => $status === 'assigned'],
                'campos_modificados' => ['permission', 'role'],
            ]);

            return response()->json([
                'success' => true,
                'status' => $status,
                'message' => 'Permiso actualizado correctamente.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
