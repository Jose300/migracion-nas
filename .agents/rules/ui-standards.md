# Estándares de Interfaz de Usuario (UI Standards)

## Botones del Sistema (Estándar Obligatorio)

En todo el sistema (modales Bootstrap, confirmaciones SweetAlert2 y formularios), se debe seguir rigurosamente el siguiente estándar de colores:

1. **Botones de Acción / Confirmación / Guardar / Crear**:
   - **Color**: Verde
   - **Bootstrap**: `btn btn-success` (`#2fb344`)
   - **SweetAlert2**: `confirmButtonColor: '#2fb344'` (`.swal2-confirm`)
   - **Ejemplos**: "GUARDAR CAMBIOS", "GUARDAR", "Sí, eliminar", "Sí, simular error", "Sí, limpiar".

2. **Botones de Cerrar / Cancelar / Descartar**:
   - **Color**: Rojo
   - **Bootstrap**: `btn btn-danger` (`#d63939`)
   - **SweetAlert2**: `cancelButtonColor: '#d63939'` (`.swal2-cancel`)
   - **Ejemplos**: "CERRAR", "Cancelar".
   - **Modales Bootstrap**: Todos los botones de pie de modal con `data-bs-dismiss="modal"` o texto "CERRAR" deben ser rojos.

3. **Alertas y Confirmaciones**:
   - Todas las alertas y confirmaciones deben ser mostradas con **SweetAlert2**. Nunca utilizar `alert()` ni `confirm()` nativos del navegador.
