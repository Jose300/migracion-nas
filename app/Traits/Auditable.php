<?php

namespace App\Traits;

use App\Services\AuditService;

trait Auditable
{
    /**
     * Boot the Auditable trait for Eloquent models.
     */
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            $data = $model->getAttributes();
            unset($data['updated_at'], $data['created_at']);

            AuditService::log([
                'modulo' => $model->getAuditModuleName(),
                'tabla' => $model->getTable(),
                'accion' => 'CREAR',
                'registro_id' => $model->getKey(),
                'registro_codigo' => (string) ($model->getKey()),
                'registro_nombre' => $model->getAuditRecordName(),
                'data_anterior' => null,
                'data_nueva' => $data,
                'campos_modificados' => array_keys($data),
            ]);
        });

        static::updated(function ($model) {
            $changes = $model->getChanges();
            unset($changes['updated_at']);

            if (empty($changes)) {
                return;
            }

            $original = array_intersect_key($model->getOriginal(), $changes);

            $accion = (count($changes) === 1 && isset($changes['status']))
                ? 'CAMBIO_ESTADO'
                : 'ACTUALIZAR';

            AuditService::log([
                'modulo' => $model->getAuditModuleName(),
                'tabla' => $model->getTable(),
                'accion' => $accion,
                'registro_id' => $model->getKey(),
                'registro_codigo' => (string) ($model->getKey()),
                'registro_nombre' => $model->getAuditRecordName(),
                'data_anterior' => $original,
                'data_nueva' => $changes,
                'campos_modificados' => array_keys($changes),
            ]);
        });

        static::deleted(function ($model) {
            $original = $model->getOriginal();
            unset($original['updated_at']);

            AuditService::log([
                'modulo' => $model->getAuditModuleName(),
                'tabla' => $model->getTable(),
                'accion' => 'ELIMINAR',
                'registro_id' => $model->getKey(),
                'registro_codigo' => (string) ($model->getKey()),
                'registro_nombre' => $model->getAuditRecordName(),
                'data_anterior' => $original,
                'data_nueva' => null,
                'campos_modificados' => array_keys($original),
            ]);
        });
    }

    /**
     * Get the human readable module name for this model.
     */
    public function getAuditModuleName(): string
    {
        if (property_exists($this, 'auditModule')) {
            return $this->auditModule;
        }

        $class = class_basename($this);
        return match ($class) {
            'User' => 'Usuarios',
            'Role' => 'Roles',
            'Permission' => 'Permisos',
            default => $class,
        };
    }

    /**
     * Get human readable name of the record.
     */
    public function getAuditRecordName(): string
    {
        return $this->name 
            ?? $this->title 
            ?? $this->description 
            ?? ("#{$this->getKey()}");
    }
}
