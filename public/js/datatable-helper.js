/**
 * Inversiones Kombi - Global DataTable Helper & Spanish Localization
 * With Automatic Excel Export & Corporate Formatting
 */

window.AppDataTableSpanish = {
    processing:     "Procesando...",
    search:         "<i class='ti ti-search text-muted'></i> Buscar:",
    searchPlaceholder: "Filtrar registros...",
    lengthMenu:     "Mostrar _MENU_ registros",
    info:           "Mostrando _START_ a _END_ de _TOTAL_ registros",
    infoEmpty:      "Mostrando 0 a 0 de 0 registros",
    infoFiltered:   "(filtrado de _MAX_ registros en total)",
    infoPostFix:    "",
    loadingRecords: "Cargando registros...",
    zeroRecords:    "No se encontraron registros coincidentes",
    emptyTable:     "No hay datos registrados en el sistema",
    paginate: {
        first:      '<i class="ti ti-chevrons-left"></i>',
        previous:   '<i class="ti ti-chevron-left"></i>',
        next:       '<i class="ti ti-chevron-right"></i>',
        last:       '<i class="ti ti-chevrons-right"></i>'
    },
    aria: {
        sortAscending:  ": Activar para ordenar la columna de manera ascendente",
        sortDescending: ": Activar para ordenar la columna de manera descendente"
    }
};

/**
 * Universal function to export ANY table (DOM or DataTable) to Microsoft Excel (.xlsx)
 * @param {string|HTMLElement|jQuery} selector 
 * @param {string} [customFilename] 
 */
window.exportTableToExcel = function(selector, customFilename) {
    if (typeof XLSX === 'undefined') {
        if (window.Swal) {
            Swal.fire({
                icon: 'error',
                title: 'Error de Librería',
                text: 'La librería de exportación a Excel no está disponible en este momento.',
                confirmButtonColor: '#206bc4'
            });
        } else {
            alert('Error: La librería XLSX no se encuentra cargada.');
        }
        return;
    }

    // Check module download permission
    if (!window.canUserExportExcel(selector)) {
        if (window.Swal) {
            Swal.fire({
                icon: 'error',
                title: 'Acceso Restringido',
                text: 'Usted no posee permisos para descargar el listado de este módulo en formato Excel.',
                confirmButtonColor: '#206bc4'
            });
        } else {
            alert('Usted no posee permisos para descargar el listado de este módulo en formato Excel.');
        }
        return;
    }

    const $table = $(selector);
    if (!$table.length) {
        console.warn('[Excel Export] Tabla no encontrada para el selector:', selector);
        return;
    }

    // Determine Report Title and Filename
    let reportTitle = customFilename || $table.data('filename') || '';
    if (!reportTitle) {
        // Try to obtain title from card header or page header
        const cardTitle = $table.closest('.card').find('.card-title, h3, h4').first().text().trim();
        const pageTitle = $('.page-title, h1, h2').first().text().trim();
        reportTitle = cardTitle || pageTitle || 'Listado_Sistema';
    }

    // Clean filename
    const cleanFilename = reportTitle
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-zA-Z0-9_\-\s]/g, '')
        .trim()
        .replace(/\s+/g, '_');

    const today = new Date();
    const dateFormatted = today.getFullYear() + '-' + 
        String(today.getMonth() + 1).padStart(2, '0') + '-' + 
        String(today.getDate()).padStart(2, '0');
    const finalFilename = `${cleanFilename}_${dateFormatted}`;

    // 1. Detect Headers & Column Indices (skipping action columns)
    const headers = [];
    const colIndices = [];

    // Find first visible header row
    const $headerRow = $table.find('thead tr').first();
    $headerRow.find('th').each(function(index) {
        const $th = $(this);
        // Extract text removing nested buttons or tags
        const $clone = $th.clone();
        $clone.find('button, .btn, i, svg, .dropdown, select, input').remove();
        let headerText = $clone.text().trim();
        const headerLower = headerText.toLowerCase();

        // Check if excluded (action button columns or columns explicitly marked no-export)
        // Note: Singular "acción" or "accion" is a business data column (e.g. audit logs) and must NOT be excluded.
        if ($th.hasClass('no-export') || 
            headerLower === 'acciones' || 
            headerLower === 'opciones' || 
            headerLower === 'operaciones' ||
            ($th.hasClass('w-1') && (headerLower === 'detalles' || headerLower === 'detalle')) ||
            ($th.hasClass('no-sort') && !headerText)) {
            return; // skip this column
        }

        if (!headerText) {
            headerText = `Columna_${index + 1}`;
        }

        colIndices.push(index);
        headers.push(headerText.replace(/\s+/g, ' '));
    });

    if (headers.length === 0) {
        if (window.Swal) {
            Swal.fire({
                icon: 'warning',
                title: 'Atención',
                text: 'No se encontraron columnas exportables en esta tabla.',
                confirmButtonColor: '#206bc4'
            });
        }
        return;
    }

    // 2. Extract Data Rows
    const dataRows = [];
    const isDataTable = $.fn.DataTable && $.fn.DataTable.isDataTable($table);

    // Helper to sanitize cell HTML into clean text for spreadsheet
    function sanitizeCell($cell) {
        if (!$cell.length) return '';
        const $c = $cell.clone();

        // Remove action buttons, tooltips, dropdowns, hidden elements, avatars (initials badges), icons
        $c.find('button, .btn, .dropdown, .dropdown-menu, script, style, .d-none, .avatar, svg, i').remove();

        // Convert breaks/divs to spaces
        $c.find('br').replaceWith(' | ');
        $c.find('div, p, span.badge').after(' ');

        let text = $c.text();
        text = text.replace(/[\r\n\t]+/g, ' ').replace(/\s{2,}/g, ' ').trim();
        text = text.replace(/\|\s*\|/g, '|').replace(/^\|\s*|\s*\|$/g, '');
        return text;
    }

    if (isDataTable) {
        const dt = $table.DataTable();
        // Export all rows currently matching active filters
        dt.rows({ search: 'applied' }).every(function() {
            const rowNode = this.node();
            if (rowNode) {
                const rowData = [];
                const $cells = $(rowNode).children('td');
                colIndices.forEach(colIdx => {
                    const $cell = $cells.eq(colIdx);
                    rowData.push(sanitizeCell($cell));
                });
                dataRows.push(rowData);
            }
        });
    } else {
        // Plain Table
        $table.find('tbody tr').each(function() {
            const $row = $(this);
            // Skip empty/placeholder rows
            if ($row.find('.dataTables_empty').length || $row.children('td').length <= 1 && $row.text().includes('No hay')) {
                return;
            }
            const rowData = [];
            const $cells = $row.children('td');
            colIndices.forEach(colIdx => {
                const $cell = $cells.eq(colIdx);
                rowData.push(sanitizeCell($cell));
            });
            if (rowData.length) {
                dataRows.push(rowData);
            }
        });
    }

    if (dataRows.length === 0) {
        if (window.Swal) {
            Swal.fire({
                icon: 'info',
                title: 'Sin Registros',
                text: 'No hay datos en el listado para exportar.',
                confirmButtonColor: '#206bc4'
            });
        }
        return;
    }

    // 3. Delegate to Universal Excel Export Engine
    window.exportDataToExcel(reportTitle, headers, dataRows, finalFilename);
};

/**
 * Universal function to export raw array data to styled Microsoft Excel (.xlsx)
 * @param {string} reportTitle 
 * @param {Array<string>} headers 
 * @param {Array<Array<any>>} dataRows 
 * @param {string} [customFilename] 
 */
window.exportDataToExcel = function(reportTitle, headers, dataRows, customFilename) {
    if (typeof XLSX === 'undefined') {
        if (window.Swal) {
            Swal.fire({
                icon: 'error',
                title: 'Error de Librería',
                text: 'La librería de exportación a Excel no está disponible en este momento.',
                confirmButtonColor: '#206bc4'
            });
        } else {
            alert('Error: La librería XLSX no se encuentra cargada.');
        }
        return;
    }

    if (!dataRows || !dataRows.length) {
        if (window.Swal) {
            Swal.fire({
                icon: 'info',
                title: 'Sin Registros',
                text: 'No hay datos en el listado para exportar.',
                confirmButtonColor: '#206bc4'
            });
        }
        return;
    }

    const title = reportTitle || 'Listado_Sistema';
    const cleanFilename = (customFilename || title)
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-zA-Z0-9_\-\s]/g, '')
        .trim()
        .replace(/\s+/g, '_');

    const today = new Date();
    const dateFormatted = today.getFullYear() + '-' + 
        String(today.getMonth() + 1).padStart(2, '0') + '-' + 
        String(today.getDate()).padStart(2, '0');
    const finalFilename = `${cleanFilename}_${dateFormatted}`;

    // 1. Build Sheet Array of Arrays (AOA) with Corporate Letterhead
    const sheetData = [];
    sheetData.push(['INVERSIONES KOMBI .EXPRESS - SISTEMA DE GESTIÓN']);
    sheetData.push([`REPORTE: ${title.toUpperCase()}`]);
    sheetData.push([`FECHA DE EXPORTACIÓN: ${today.toLocaleDateString('es-VE')} ${today.toLocaleTimeString('es-VE')}`]);
    sheetData.push([`TOTAL REGISTROS: ${dataRows.length}`]);
    sheetData.push([]); // Empty row separator
    sheetData.push(headers); // Column Headers

    dataRows.forEach(r => sheetData.push(r));

    // 2. Create Sheet & Auto-fit Column Widths
    const ws = XLSX.utils.aoa_to_sheet(sheetData);

    const colWidths = headers.map((h, i) => {
        let maxLen = h.length;
        dataRows.forEach(row => {
            const rawVal = row[i] !== null && row[i] !== undefined ? String(row[i]) : '';
            const firstLine = rawVal.split('\n')[0];
            if (firstLine.length > maxLen) {
                maxLen = firstLine.length;
            }
        });
        return { wch: Math.min(Math.max(maxLen + 3, 14), 60) };
    });
    ws['!cols'] = colWidths;

    // 3. Apply Corporate Styles (Bold Company info & Blue Header Bar)
    const headerMetadataStyles = [
        { font: { bold: true, sz: 12, color: { rgb: "0F172A" }, name: "Calibri" }, alignment: { vertical: "center" } },
        { font: { bold: true, sz: 11, color: { rgb: "1E293B" }, name: "Calibri" }, alignment: { vertical: "center" } },
        { font: { bold: true, sz: 10, color: { rgb: "334155" }, name: "Calibri" }, alignment: { vertical: "center" } },
        { font: { bold: true, sz: 10, color: { rgb: "334155" }, name: "Calibri" }, alignment: { vertical: "center" } }
    ];

    ['A1', 'A2', 'A3', 'A4'].forEach((cellAddr, idx) => {
        if (ws[cellAddr]) {
            ws[cellAddr].s = headerMetadataStyles[idx];
        }
    });

    // Style Row 6 (index 5): Table Column Header Bar in Blue with Bold White Text
    const headerRowIdx = 5;
    for (let c = 0; c < headers.length; c++) {
        const addr = XLSX.utils.encode_cell({ r: headerRowIdx, c: c });
        if (ws[addr]) {
            ws[addr].s = {
                fill: { fgColor: { rgb: "206BC4" } },
                font: { bold: true, color: { rgb: "FFFFFF" }, sz: 11, name: "Calibri" },
                alignment: { vertical: "center", horizontal: "center", wrapText: true },
                border: {
                    top: { style: "thin", color: { rgb: "15529A" } },
                    bottom: { style: "medium", color: { rgb: "15529A" } },
                    left: { style: "thin", color: { rgb: "357CD4" } },
                    right: { style: "thin", color: { rgb: "357CD4" } }
                }
            };
        }
    }

    // Style Data Rows (index 6 onward): Subtle borders and zebra striping
    const dataStartRowIdx = 6;
    for (let r = dataStartRowIdx; r < sheetData.length; r++) {
        const isEven = (r - dataStartRowIdx) % 2 === 1;
        const rowBgColor = isEven ? "F8FAFC" : "FFFFFF";

        for (let c = 0; c < headers.length; c++) {
            const addr = XLSX.utils.encode_cell({ r: r, c: c });
            if (ws[addr]) {
                ws[addr].s = {
                    fill: { fgColor: { rgb: rowBgColor } },
                    font: { sz: 10, color: { rgb: "1E293B" }, name: "Calibri" },
                    alignment: { vertical: "center" },
                    border: {
                        top: { style: "thin", color: { rgb: "E2E8F0" } },
                        bottom: { style: "thin", color: { rgb: "E2E8F0" } },
                        left: { style: "thin", color: { rgb: "E2E8F0" } },
                        right: { style: "thin", color: { rgb: "E2E8F0" } }
                    }
                };
            }
        }
    }

    // Set row heights for a spacious, professional layout
    const rowHeights = [
        { hpt: 22 }, // Row 1: Company
        { hpt: 19 }, // Row 2: Report Title
        { hpt: 17 }, // Row 3: Date
        { hpt: 17 }, // Row 4: Total records
        { hpt: 10 }, // Row 5: Gap
        { hpt: 26 }  // Row 6: Blue Header Bar
    ];
    for (let r = dataStartRowIdx; r < sheetData.length; r++) {
        rowHeights[r] = { hpt: 20 };
    }
    ws['!rows'] = rowHeights;

    // 4. Build Workbook & Download
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Listado");
    XLSX.writeFile(wb, `${finalFilename}.xlsx`);

    // 5. User Feedback
    if (window.Swal) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: `Excel descargado (${dataRows.length} registros)`,
            showConfirmButton: false,
            timer: 3500
        });
    }
};

/**
 * Detect module key for permission checking based on table or current URL
 * @param {string|HTMLElement|jQuery} selector
 * @returns {string|null}
 */
window.getTableModuleKey = function(selector) {
    const $tbl = $(selector);
    if ($tbl.length) {
        if ($tbl.data('module')) return String($tbl.data('module')).toLowerCase();
        const id = ($tbl.attr('id') || '').toLowerCase();
        if (id.includes('vendedor')) return 'vendedores';
        if (id.includes('cliente')) return 'clientes';
        if (id.includes('visita')) return 'visitas';
        if (id.includes('producto') || id.includes('categoria') || id.includes('recarga') || id.includes('inventario')) return 'inventario';
        if (id.includes('venta') || id.includes('orden')) return 'ventas';
        if (id.includes('despacho')) return 'despachos';
        if (id.includes('credito')) return 'creditos';
        if (id.includes('pago')) return 'pagos';
        if (id.includes('comision')) return 'comisiones';
        if (id.includes('movimiento') || id.includes('finanza')) return 'finanzas';
        if (id.includes('usuario')) return 'usuarios';
        if (id.includes('rol')) return 'roles';
        if (id.includes('permiso')) return 'permisos';
        if (id.includes('auditoria')) return 'auditoria';
        if (id.includes('alerta')) return 'alertas';
        if (id.includes('error')) return 'errores';
    }

    // Fallback to URL pathname
    const path = window.location.pathname.toLowerCase();
    if (path.includes('vendedor')) return 'vendedores';
    if (path.includes('cliente')) return 'clientes';
    if (path.includes('visita')) return 'visitas';
    if (path.includes('inventario')) return 'inventario';
    if (path.includes('venta')) return 'ventas';
    if (path.includes('despacho')) return 'despachos';
    if (path.includes('credito')) return 'creditos';
    if (path.includes('pago')) return 'pagos';
    if (path.includes('comision')) return 'comisiones';
    if (path.includes('finanza')) return 'finanzas';
    if (path.includes('usuario')) return 'usuarios';
    if (path.includes('roles')) return 'roles';
    if (path.includes('permiso')) return 'permisos';
    if (path.includes('auditoria')) return 'auditoria';
    if (path.includes('alerta')) return 'alertas';
    if (path.includes('log-error') || path.includes('errores')) return 'errores';

    return null;
};

/**
 * Check if the currently authenticated user is allowed to download Excel for this table/module
 * @param {string|HTMLElement|jQuery} selector
 * @returns {boolean}
 */
window.canUserExportExcel = function(selector) {
    if (typeof window.UserExcelPermissions === 'undefined') {
        return true;
    }
    const moduleKey = window.getTableModuleKey(selector);
    if (!moduleKey) {
        return true;
    }
    if (typeof window.UserExcelPermissions[moduleKey] !== 'undefined') {
        return Boolean(window.UserExcelPermissions[moduleKey]);
    }
    return true;
};

/**
 * Helper to initialize a standard responsive Tabler DataTable with Excel button
 * @param {string} selector 
 * @param {object} customOptions 
 */
function initStandardDataTable(selector, customOptions = {}) {
    if (window.jQuery && $.fn.DataTable && $(selector).length) {
        const defaultOptions = {
            responsive: false,
            autoWidth: false,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
            order: [[0, 'desc']],
            columnDefs: [
                { targets: 'no-sort', orderable: false }
            ],
            language: window.AppDataTableSpanish,
            dom: "<'card-body border-bottom py-3'<'d-flex align-items-center justify-content-between flex-wrap gap-2'<'text-secondary'l><'d-flex align-items-center flex-wrap gap-2 ms-auto'<'dt-excel-slot'>f>>>" +
                 "<'table-responsive'tr>" +
                 "<'card-footer d-flex align-items-center justify-content-between flex-wrap gap-2'<'text-secondary m-0'i><'pagination m-0 ms-auto'p>>",
            drawCallback: function() {
                $(selector).closest('.card').find('.dataTables_paginate > .pagination').addClass('pagination-sm mb-0');
            },
            initComplete: function(settings, json) {
                const canExport = window.canUserExportExcel(selector);
                const $wrapper = $(selector).closest('.dataTables_wrapper');
                const $slot = $wrapper.find('.dt-excel-slot');
                if ($slot.length) {
                    if (canExport && !$slot.find('.btn-export-excel').length) {
                        const tableId = $(selector).attr('id') || '';
                        $slot.html(`
                            <button type="button" class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1 shadow-xs btn-export-excel" data-table="#${tableId}" title="Descargar este listado en formato Excel">
                                <i class="ti ti-file-spreadsheet fs-3"></i> <span>Exportar a Excel</span>
                            </button>
                        `);
                    } else if (!canExport) {
                        $slot.empty();
                    }
                }
            }
        };

        const dtInstance = $(selector).DataTable(Object.assign(defaultOptions, customOptions));

        // Fallback check to ensure button injection or removal according to permissions
        const canExport = window.canUserExportExcel(selector);
        const $wrapper = $(selector).closest('.dataTables_wrapper');
        const $slot = $wrapper.find('.dt-excel-slot');
        if ($slot.length) {
            if (canExport && !$slot.find('.btn-export-excel').length) {
                const tableId = $(selector).attr('id') || '';
                $slot.html(`
                    <button type="button" class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1 shadow-xs btn-export-excel" data-table="#${tableId}" title="Descargar este listado en formato Excel">
                        <i class="ti ti-file-spreadsheet fs-3"></i> <span>Exportar a Excel</span>
                    </button>
                `);
            } else if (!canExport) {
                $slot.empty();
            }
        }

        return dtInstance;
    }
    return null;
}

// Global click delegation for any Excel export button anywhere in the application
$(document).on('click', '.btn-export-excel, .btn-export-dt-excel', function(e) {
    e.preventDefault();
    const tableTarget = $(this).data('table') || $(this).closest('.card').find('table').first();
    if (!window.canUserExportExcel(tableTarget)) {
        if (window.Swal) {
            Swal.fire({
                icon: 'error',
                title: 'Acceso Restringido',
                text: 'Usted no posee permisos para descargar el listado de este módulo en formato Excel.',
                confirmButtonColor: '#206bc4'
            });
        } else {
            alert('Usted no posee permisos para descargar el listado de este módulo en formato Excel.');
        }
        return;
    }
    const customFilename = $(this).data('filename');
    window.exportTableToExcel(tableTarget, customFilename);
});
