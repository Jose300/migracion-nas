document.addEventListener('DOMContentLoaded', function() {
    const userModal = new bootstrap.Modal(document.getElementById('modal-user'));
    const userForm = document.getElementById('user-form');
    const modalTitle = document.getElementById('modal-user-title');
    const btnSaveUser = document.getElementById('btn-save-user');
    const alertBox = document.getElementById('user-alert-error');
    const errorList = document.getElementById('user-error-list');

    // Handle "Add User" button
    const btnAddUser = document.querySelector('.btn-add-user');
    if (btnAddUser) {
        btnAddUser.addEventListener('click', function() {
            userForm.reset();
            alertBox.classList.add('d-none');
            modalTitle.textContent = 'Agregar Nuevo Usuario';
            userForm.action = this.dataset.url;
            // Set method to POST for creation
            const methodInput = userForm.querySelector('input[name="_method"]');
            if (methodInput) methodInput.remove();
            
            // Show password fields as required for new users
            const passFields = userForm.querySelectorAll('input[type="password"]');
            passFields.forEach(f => f.closest('.col-md-6').querySelector('label').classList.add('required'));
            
            // Hide password hint for new users
            document.getElementById('password-hint').classList.add('d-none');
            
            updateModalAreaCodes('+58', '');
            userModal.show();
        });
    }

    // Handle "Edit User" buttons
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-edit-user')) {
            const btn = e.target.closest('.btn-edit-user');
            const url = btn.dataset.url;
            const updateUrl = btn.dataset.updateUrl;

            alertBox.classList.add('d-none');
            modalTitle.textContent = 'Editar Usuario';
            userForm.action = updateUrl;
            
            // Add method PUT for update
            let methodInput = userForm.querySelector('input[name="_method"]');
            if (!methodInput) {
                methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'PUT';
                userForm.appendChild(methodInput);
            }

            // Remove required class from password labels for editing
            const passFields = userForm.querySelectorAll('input[type="password"]');
            passFields.forEach(f => f.closest('.col-md-6').querySelector('label').classList.remove('required'));

            // Show password hint for editing
            document.getElementById('password-hint').classList.remove('d-none');

            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                if (userForm.first_name) userForm.first_name.value = data.usuario.first_name || '';
                if (userForm.last_name) userForm.last_name.value = data.usuario.last_name || '';
                if (userForm.name) userForm.name.value = data.usuario.name || '';
                userForm.email.value = data.usuario.email;
                const country = data.usuario.country_code || '+58';
                const area = data.usuario.area_code || '';
                if (userForm.country_code) {
                    userForm.country_code.value = country;
                }
                updateModalAreaCodes(country, area);
                updateModalFlag();
                if (userForm.phone) userForm.phone.value = data.usuario.phone || '';
                userForm.role.value = data.role;
                userForm.status.value = data.usuario.status;
                userForm.password.value = '';
                userForm.password_confirmation.value = '';
                userModal.show();
            });
        }
    });

    // Handle Form Submission
    userForm.addEventListener('submit', function(e) {
        e.preventDefault();
        btnSaveUser.disabled = true;
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
                userModal.hide();
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
            btnSaveUser.disabled = false;
        })
        .catch(error => {
            console.error('Error:', error);
            btnSaveUser.disabled = false;
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
                text: `Desea eliminar al usuario "${name}". Esta acción no se puede deshacer.`,
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

    // Handle Password Visibility Toggle
    document.querySelectorAll('.toggle-password').forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const input = this.closest('.input-group').querySelector('input');
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('ti-eye');
                icon.classList.add('ti-eye-off');
                this.setAttribute('title', 'Ocultar contraseña');
            } else {
                input.type = 'password';
                icon.classList.remove('ti-eye-off');
                icon.classList.add('ti-eye');
                this.setAttribute('title', 'Mostrar contraseña');
            }
        });
    });

    // Handle Status Color Change
    const statusSelect = document.getElementById('user-status-select');
    if (statusSelect) {
        const updateStatusColor = () => {
            statusSelect.classList.remove('text-success', 'text-danger');
            if (statusSelect.value === 'Activo') {
                statusSelect.classList.add('text-success');
            } else {
                statusSelect.classList.add('text-danger');
            }
        };

        statusSelect.addEventListener('change', updateStatusColor);
        
        // Also update when modal opens (needs a small delay or event hook)
        document.getElementById('modal-user').addEventListener('shown.bs.modal', updateStatusColor);
    }

    // Handle Country Code & Area Code Dynamic Population in Modal
    const userCountrySelect = document.getElementById('user-country-code');
    const userModalFlagIcon = document.getElementById('user-modal-flag-icon');

    function updateModalFlag() {
        if (!userCountrySelect || !userModalFlagIcon) return;
        const opt = userCountrySelect.options[userCountrySelect.selectedIndex];
        const iso = opt ? opt.getAttribute('data-iso') : 've';
        userModalFlagIcon.className = 'flag flag-xs flag-country-' + (iso || 've');
    }

    function updateModalAreaCodes(countryCode, selectedAreaCode = '') {
        const userAreaSelect = document.getElementById('user-area-code');
        if (!userAreaSelect) return;
        
        const dictionary = window.phoneAreaDictionary || {};
        const areas = dictionary[countryCode] || {};
        userAreaSelect.innerHTML = '<option value="">-- Seleccionar --</option>';
        
        let found = false;
        const targetVal = String(selectedAreaCode || '').trim();

        for (const [val, label] of Object.entries(areas)) {
            const opt = document.createElement('option');
            opt.value = val;
            opt.textContent = label;
            if (targetVal && (String(val) === targetVal || String(val) === '0' + targetVal || '0' + String(val) === targetVal)) {
                opt.selected = true;
                found = true;
            }
            userAreaSelect.appendChild(opt);
        }
        
        if (targetVal && !found) {
            const customOpt = document.createElement('option');
            customOpt.value = targetVal;
            customOpt.textContent = targetVal;
            customOpt.selected = true;
            userAreaSelect.appendChild(customOpt);
        }
    }

    if (userCountrySelect) {
        userCountrySelect.addEventListener('change', function() {
            updateModalFlag();
            updateModalAreaCodes(this.value, userForm.area_code ? userForm.area_code.value : '');
        });
        document.getElementById('modal-user')?.addEventListener('shown.bs.modal', updateModalFlag);
    }
});
