<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Http\Requests\Config\StoreRoleRequest;
use App\Http\Requests\Config\UpdateRoleRequest;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;

class RolesController extends Controller
{
    public function index()
    {
        $roles = Role::paginate(10);
        return view('config.roles.index', compact('roles'));
    }

    public function store(StoreRoleRequest $request)
    {
        $role = Role::create(['name' => $request->name]);

        \App\Services\AuditService::log([
            'modulo' => 'Roles',
            'tabla' => 'roles',
            'accion' => 'CREAR',
            'registro_id' => $role->id,
            'registro_codigo' => (string) $role->id,
            'registro_nombre' => $role->name,
            'data_nueva' => ['name' => $role->name],
            'campos_modificados' => ['name'],
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Rol creado correctamente.'
            ]);
        }

        return redirect()->route('roles.index')->with('success', 'Rol creado correctamente.');
    }

    public function edit(Role $role)
    {
        return response()->json(['role' => $role]);
    }

    public function update(UpdateRoleRequest $request, Role $role)
    {
        if (strtolower($role->name) === 'develop') {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El rol Develop es del sistema y no puede ser modificado.'
                ], 403);
            }
            return redirect()->route('roles.index')->with('error', 'El rol Develop es del sistema y no puede ser modificado.');
        }

        $oldName = $role->name;
        $role->update(['name' => $request->name]);

        \App\Services\AuditService::log([
            'modulo' => 'Roles',
            'tabla' => 'roles',
            'accion' => 'ACTUALIZAR',
            'registro_id' => $role->id,
            'registro_codigo' => (string) $role->id,
            'registro_nombre' => $role->name,
            'data_anterior' => ['name' => $oldName],
            'data_nueva' => ['name' => $role->name],
            'campos_modificados' => ['name'],
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Rol actualizado correctamente.'
            ]);
        }

        return redirect()->route('roles.index')->with('success', 'Rol actualizado correctamente.');
    }

    public function destroy(Role $role)
    {
        if (strtolower($role->name) === 'develop') {
            return redirect()->route('roles.index')->with('error', 'El rol Develop es del sistema y no puede ser eliminado.');
        }

        $deletedName = $role->name;
        $deletedId = $role->id;
        $role->delete();

        \App\Services\AuditService::log([
            'modulo' => 'Roles',
            'tabla' => 'roles',
            'accion' => 'ELIMINAR',
            'registro_id' => $deletedId,
            'registro_codigo' => (string) $deletedId,
            'registro_nombre' => $deletedName,
            'data_anterior' => ['name' => $deletedName],
            'campos_modificados' => ['name'],
        ]);

        return redirect()->route('roles.index')->with('success', 'Rol eliminado correctamente.');
    }
}
