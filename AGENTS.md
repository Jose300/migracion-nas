# Reglas y Estándares del Proyecto (Sistema Base)

## Estándar de Botones y Modales (UI)
- **Botones de acción (Guardar, Crear, Confirmar, Simular, etc.)**: Siempre en **VERDE** (`btn-success` / `#2fb344` / `.swal2-confirm`).
- **Botones de cerrar o cancelar (Cerrar, Cancelar, Descartar, etc.)**: Siempre en **ROJO** (`btn-danger` / `#d63939` / `.swal2-cancel`).
- **Alertas y confirmaciones**: Siempre utilizar **SweetAlert2**. Prohibido el uso de `alert()` o `confirm()` nativos del navegador.
- **Módulos independientes**: El módulo "Log de Errores" debe mantenerse siempre fuera e independiente de "Auditoría".
