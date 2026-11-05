document.addEventListener('DOMContentLoaded', function() {
    const roleModal = new bootstrap.Modal(document.getElementById('modal-role'));
    const roleForm = document.getElementById('role-form');
    const modalTitle = document.getElementById('modal-role-title');
    const btnSaveRole = document.getElementById('btn-save-role');
    const alertBox = document.getElementById('role-alert-error');
    const errorList = document.getElementById('role-error-list');

    // Handle "Add Role" button
    const btnAddRole = document.querySelector('.btn-add-role');
    if (btnAddRole) {
        btnAddRole.addEventListener('click', function() {
            roleForm.reset();
            alertBox.classList.add('d-none');
            modalTitle.textContent = 'Agregar Nuevo Rol';
            roleForm.action = this.dataset.url;
            const methodInput = roleForm.querySelector('input[name="_method"]');
            if (methodInput) methodInput.remove();
            roleModal.show();
        });
    }

    // Handle "Edit Role" buttons
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-edit-role')) {
            const btn = e.target.closest('.btn-edit-role');
            const url = btn.dataset.url;
            const updateUrl = btn.dataset.updateUrl;

            alertBox.classList.add('d-none');
            modalTitle.textContent = 'Editar Rol';
            roleForm.action = updateUrl;
            
            let methodInput = roleForm.querySelector('input[name="_method"]');
            if (!methodInput) {
                methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'PUT';
                roleForm.appendChild(methodInput);
            }

            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                roleForm.name.value = data.role.name;
                roleModal.show();
            });
        }
    });

    // Handle Form Submission
    roleForm.addEventListener('submit', function(e) {
        e.preventDefault();
        btnSaveRole.disabled = true;
        alertBox.classList.add('d-none');
        errorList.innerHTML = '';

        const formData = new FormData(this);

        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                roleModal.hide();
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: data.message,
                    timer: 2000,
                    showConfirmButton: false,
                    confirmButtonColor: '#206bc4'
                }).then(() => {
                    location.reload();
                });
            } else if (data.errors) {
                alertBox.classList.remove('d-none');
                Object.values(data.errors).forEach(err => {
                    const li = document.createElement('li');
                    li.textContent = err[0];
                    errorList.appendChild(li);
                });
            }
            btnSaveRole.disabled = false;
        })
        .catch(error => {
            console.error('Error:', error);
            btnSaveRole.disabled = false;
        });
    });

    // Handle Delete with SweetAlert2
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-delete')) {
            const btn = e.target.closest('.btn-delete');
            const url = btn.dataset.url;
            const name = btn.dataset.name;

            Swal.fire({
                title: '¿Está seguro?',
                text: `Desea eliminar el rol "${name}". Esta acción no se puede deshacer.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#2fb344',
                cancelButtonColor: '#d63939',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = url;
                    form.innerHTML = `
                        <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]')?.content}">
                        <input type="hidden" name="_method" value="DELETE">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    });
});
