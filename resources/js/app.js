import * as bootstrap from 'bootstrap';
import Swal from 'sweetalert2';
import DataTable from 'datatables.net-bs5';
import TomSelect from 'tom-select';
import 'datatables.net-responsive-bs5';
import 'datatables.net-bs5/css/dataTables.bootstrap5.min.css';
import 'datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css';
import 'sweetalert2/dist/sweetalert2.min.css';
import 'tom-select/dist/css/tom-select.bootstrap5.min.css';

window.Swal = Swal;

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
const ajaxModalElement = document.getElementById('ajaxModal');
const ajaxModal = ajaxModalElement ? new bootstrap.Modal(ajaxModalElement) : null;
const ajaxModalTitle = document.getElementById('ajaxModalTitle');
const ajaxModalBody = ajaxModalElement?.querySelector('[data-modal-body]');
const ajaxModalDialog = ajaxModalElement?.querySelector('.modal-dialog');

const toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 2600,
    timerProgressBar: true,
});

function showInitialAlerts() {
    const success = document.querySelector('[data-swal-success]')?.dataset.swalSuccess;
    const error = document.querySelector('[data-swal-error]')?.dataset.swalError;

    if (success) {
        toast.fire({ icon: 'success', title: success });
    }

    if (error) {
        Swal.fire({ icon: 'error', title: 'Atencion', text: error });
    }
}

async function fetchHtml(url) {
    const response = await fetch(url, {
        headers: {
            Accept: 'text/html',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        throw new Error('No se pudo cargar el contenido solicitado.');
    }

    return response.text();
}

function openAjaxModal(trigger) {
    if (!ajaxModal || !ajaxModalBody || !ajaxModalTitle) {
        window.location.href = trigger.href;

        return;
    }

    ajaxModalTitle.textContent = trigger.dataset.modalTitle ?? 'Detalle';
    ajaxModalDialog?.classList.remove('modal-sm', 'modal-lg', 'modal-xl');
    ajaxModalDialog?.classList.add(`modal-${trigger.dataset.modalSize ?? 'lg'}`);
    ajaxModalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';
    ajaxModal.show();

    fetchHtml(trigger.dataset.modalUrl ?? trigger.href)
        .then((html) => {
            ajaxModalBody.innerHTML = html;
            initializeUi(ajaxModalBody);
        })
        .catch((error) => {
            ajaxModal.hide();
            Swal.fire({ icon: 'error', title: 'Error', text: error.message });
        });
}

function disableBusinessFormAutocomplete(scope = document) {
    scope.querySelectorAll('form[data-ajax-form], .form-panel form').forEach((form) => {
        form.setAttribute('autocomplete', 'off');
    });

    scope.querySelectorAll('form[data-ajax-form] input, form[data-ajax-form] textarea, .form-panel input, .form-panel textarea').forEach((field) => {
        if (['hidden', 'checkbox', 'radio', 'submit', 'button'].includes(field.type)) {
            return;
        }

        const shouldUsePasswordToken = field.name === 'name' || field.id.endsWith('-name');
        field.setAttribute('autocomplete', shouldUsePasswordToken ? 'new-password' : 'off');
        field.setAttribute('data-lpignore', 'true');
        field.setAttribute('data-1p-ignore', 'true');
    });
}

function clearFormErrors(form) {
    form.querySelectorAll('.is-invalid').forEach((field) => field.classList.remove('is-invalid'));
    form.querySelectorAll('[data-error-for]').forEach((target) => {
        target.textContent = '';
    });
}

function showFormErrors(form, errors) {
    Object.entries(errors).forEach(([field, messages]) => {
        const input = form.querySelector(`[name="${field}"]`);
        const feedback = form.querySelector(`[data-error-for="${field}"]`);

        input?.classList.add('is-invalid');

        if (feedback) {
            feedback.textContent = messages[0] ?? 'Dato invalido.';
        }
    });
}

function setSubmitting(form, submitting) {
    const submit = form.querySelector('[type="submit"]');
    const spinner = form.querySelector('[data-submit-spinner]');

    if (submit) {
        submit.disabled = submitting;
    }

    spinner?.classList.toggle('d-none', !submitting);
}

async function refreshContainer(url) {
    const current = document.querySelector('[data-refresh-container]');

    if (!current) {
        return;
    }

    const html = await fetchHtml(url ?? window.location.href);
    const documentFragment = new DOMParser().parseFromString(html, 'text/html');
    const fresh = documentFragment.querySelector('[data-refresh-container]');

    if (fresh) {
        current.replaceWith(fresh);
        initializeUi(fresh);
    }
}

async function submitAjaxForm(form) {
    clearFormErrors(form);
    setSubmitting(form, true);

    try {
        const response = await fetch(form.action, {
            method: form.method.toUpperCase(),
            body: new FormData(form),
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const payload = await response.json();

        if (response.status === 422) {
            showFormErrors(form, payload.errors ?? payload.data ?? {});
            Swal.fire({ icon: 'error', title: 'Validacion', text: payload.message ?? 'Revisa los datos ingresados.' });

            return;
        }

        if (!response.ok || payload.success === false) {
            throw new Error(payload.message ?? 'No se pudo completar la operacion.');
        }

        if (payload.redirect_url) {
            window.location.assign(payload.redirect_url);

            return;
        }

        ajaxModal?.hide();
        await refreshContainer(payload.refresh_url ?? form.dataset.refreshUrl);
        toast.fire({ icon: 'success', title: payload.message ?? 'Operacion realizada correctamente.' });
    } catch (error) {
        Swal.fire({ icon: 'error', title: 'Error', text: error.message });
    } finally {
        setSubmitting(form, false);
    }
}

function confirmDelete(form) {
    Swal.fire({
        icon: 'warning',
        title: form.dataset.confirmDelete ?? 'Confirmar eliminacion',
        text: 'Esta accion no se puede deshacer facilmente.',
        showCancelButton: true,
        confirmButtonText: 'Si, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc3545',
    }).then((result) => {
        if (result.isConfirmed) {
            if (form.matches('[data-ajax-form]')) {
                submitAjaxForm(form);

                return;
            }

            form.submit();
        }
    });
}

function initAdminDataTables(scope = document) {
    scope.querySelectorAll('[data-datatable]').forEach((table) => {
        if (table.dataset.datatableInitialized === '1') {
            return;
        }

        const columnsElement = document.getElementById(table.dataset.columnsId ?? '');
        const columns = JSON.parse(columnsElement?.textContent ?? table.dataset.columns ?? '[]');
        const filtersForm = table.dataset.filtersForm ? document.querySelector(table.dataset.filtersForm) : null;

        const dataTable = new DataTable(table, {
            ajax: {
                url: table.dataset.url,
                data(data) {
                    if (!filtersForm) {
                        return;
                    }

                    new FormData(filtersForm).forEach((value, key) => {
                        data[key] = value;
                    });
                },
            },
            columns,
            processing: true,
            serverSide: true,
            responsive: true,
            pageLength: Number(table.dataset.pageLength ?? 10),
            order: JSON.parse(table.dataset.order ?? '[[0,"desc"]]'),
            language: {
                search: 'Buscar:',
                lengthMenu: 'Mostrar _MENU_ registros',
                info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                infoEmpty: 'Sin registros',
                infoFiltered: '(filtrado de _MAX_ registros)',
                loadingRecords: 'Cargando...',
                processing: 'Procesando...',
                zeroRecords: 'No se encontraron registros',
                emptyTable: 'No hay datos disponibles',
                paginate: {
                    first: 'Primero',
                    previous: 'Anterior',
                    next: 'Siguiente',
                    last: 'Ultimo',
                },
            },
        });

        if (filtersForm) {
            let reloadTimeout;
            const reloadTable = () => {
                window.clearTimeout(reloadTimeout);
                reloadTimeout = window.setTimeout(() => dataTable.ajax.reload(), 180);
            };

            filtersForm.addEventListener('change', reloadTable);
            filtersForm.addEventListener('reset', () => {
                window.setTimeout(() => {
                    filtersForm.querySelectorAll('select[data-tom-select]').forEach((select) => {
                        select.tomselect?.clear(true);
                    });
                    dataTable.ajax.reload();
                }, 0);
            });
        }

        dataTable.on('xhr.dt', (_event, _settings, _json, xhr) => {
            if (xhr.status === 401 || xhr.status === 403 || xhr.responseURL?.includes('/login')) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sin acceso',
                    text: 'No tienes permisos para cargar los datos de esta tabla o tu sesion expiro.',
                });
            }
        });
        table.dataset.datatableInitialized = '1';
    });
}

function initTomSelects(scope = document) {
    scope.querySelectorAll('select[data-tom-select]').forEach((select) => {
        if (select.tomselect) {
            return;
        }

        new TomSelect(select, {
            allowEmptyOption: true,
            create: false,
            dropdownParent: 'body',
            maxItems: select.multiple ? null : 1,
            placeholder: select.dataset.placeholder ?? 'Seleccionar',
            plugins: ['clear_button'],
            render: {
                no_results() {
                    return '<div class="no-results">Sin resultados</div>';
                },
            },
        });
    });
}

function initCharacterCounters(scope = document) {
    scope.querySelectorAll('[data-character-counter]').forEach((field) => {
        if (field.dataset.characterCounterInitialized === '1') {
            return;
        }

        const target = scope.querySelector(field.dataset.characterCounter) ?? document.querySelector(field.dataset.characterCounter);

        if (!target) {
            return;
        }

        const min = Number(field.getAttribute('minlength') || 0);
        const max = Number(field.getAttribute('maxlength') || 0);
        const update = () => {
            const length = field.value.length;
            const minimum = min ? ` / minimo ${min}` : '';
            const maximum = max ? ` / maximo ${max}` : '';
            target.textContent = `${length} caracteres${minimum}${maximum}`;
            target.classList.toggle('text-danger', (min && length < min) || (max && length > max));
            target.classList.toggle('text-success', (!min || length >= min) && (!max || length <= max));
        };

        field.addEventListener('input', update);
        field.dataset.characterCounterInitialized = '1';
        update();
    });
}

function initImagePickers(scope = document) {
    scope.querySelectorAll('input[type="file"][accept*="image"]').forEach((input) => {
        if (input.dataset.imagePickerInitialized === '1') {
            return;
        }

        input.dataset.imagePickerInitialized = '1';
        const preview = document.createElement('div');
        preview.className = 'image-picker-preview';
        preview.setAttribute('aria-live', 'polite');
        input.insertAdjacentElement('afterend', preview);

        const render = () => {
            preview.querySelectorAll('img').forEach((image) => URL.revokeObjectURL(image.src));
            preview.replaceChildren();

            [...input.files].forEach((file, index) => {
                const item = document.createElement('div');
                item.className = 'image-picker-item';

                const image = document.createElement('img');
                image.src = URL.createObjectURL(file);
                image.alt = `Vista previa de ${file.name}`;

                const details = document.createElement('div');
                details.className = 'image-picker-details';
                details.innerHTML = `<span class="image-picker-name"></span><span class="text-body-secondary small">${formatFileSize(file.size)} - se guardara como WebP</span>`;
                details.querySelector('.image-picker-name').textContent = file.name;

                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'btn btn-outline-danger btn-icon image-picker-remove';
                remove.setAttribute('aria-label', `Quitar ${file.name} antes de guardar`);
                remove.innerHTML = '<i class="ti ti-trash" aria-hidden="true"></i>';
                remove.addEventListener('click', () => removeSelectedImage(input, index, render));

                item.append(image, details, remove);
                preview.append(item);
            });
        };

        input.addEventListener('change', render);
        render();
    });
}

function removeSelectedImage(input, removedIndex, render) {
    const transfer = new DataTransfer();

    [...input.files].forEach((file, index) => {
        if (index !== removedIndex) {
            transfer.items.add(file);
        }
    });

    input.files = transfer.files;
    input.dispatchEvent(new Event('change', { bubbles: true }));
    render();
}

function formatFileSize(bytes) {
    if (bytes < 1024 * 1024) {
        return `${Math.max(1, Math.round(bytes / 1024))} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function initPermissionMatrices(scope = document) {
    scope.querySelectorAll('[data-permission-matrix]').forEach((matrix) => {
        if (matrix.dataset.permissionMatrixInitialized === '1') {
            return;
        }

        const checkboxes = [...matrix.querySelectorAll('[data-permission-checkbox]')];
        const modules = [...matrix.querySelectorAll('[data-permission-module]')];
        const search = matrix.querySelector('[data-permission-search]');
        const empty = matrix.querySelector('[data-permission-empty]');
        const selectedCount = matrix.querySelector('[data-permission-selected-count]');
        const visibleOptions = () => checkboxes.filter((checkbox) => !checkbox.closest('[data-permission-option]')?.classList.contains('d-none'));

        const update = () => {
            if (selectedCount) {
                selectedCount.textContent = String(checkboxes.filter((checkbox) => checkbox.checked).length);
            }

            modules.forEach((module) => {
                const moduleCheckboxes = [...module.querySelectorAll('[data-permission-checkbox]')];
                const moduleCount = module.querySelector('[data-module-selected-count]');
                const moduleButton = module.querySelector('[data-permission-select-module]');
                const checked = moduleCheckboxes.filter((checkbox) => checkbox.checked).length;

                if (moduleCount) {
                    moduleCount.textContent = String(checked);
                }

                if (moduleButton) {
                    const allSelected = checked === moduleCheckboxes.length;
                    moduleButton.textContent = allSelected ? 'Desactivar todos' : 'Activar todos';
                    moduleButton.classList.toggle('btn-ghost-danger', allSelected);
                    moduleButton.classList.toggle('btn-ghost-primary', !allSelected);
                }
            });
        };

        const filter = () => {
            const query = search?.value.trim().toLocaleLowerCase('es') ?? '';
            let visibleModules = 0;

            modules.forEach((module) => {
                const options = [...module.querySelectorAll('[data-permission-option]')];
                const moduleMatches = module.dataset.searchText?.includes(query) ?? false;
                let visibleOptionCount = 0;

                options.forEach((option) => {
                    const matches = !query || moduleMatches || option.dataset.searchText?.includes(query);
                    option.classList.toggle('d-none', !matches);
                    visibleOptionCount += matches ? 1 : 0;
                });

                const visible = visibleOptionCount > 0;
                module.classList.toggle('d-none', !visible);
                visibleModules += visible ? 1 : 0;

                if (query && visible) {
                    bootstrap.Collapse.getOrCreateInstance(module.querySelector('[data-permission-module-panel]'), { toggle: false }).show();
                }
            });

            empty?.classList.toggle('d-none', visibleModules > 0);
        };

        checkboxes.forEach((checkbox) => checkbox.addEventListener('change', update));
        search?.addEventListener('input', filter);

        matrix.querySelector('[data-permission-select-visible]')?.addEventListener('click', () => {
            visibleOptions().forEach((checkbox) => {
                checkbox.checked = true;
            });
            update();
        });

        matrix.querySelector('[data-permission-clear]')?.addEventListener('click', () => {
            checkboxes.forEach((checkbox) => {
                checkbox.checked = false;
            });
            update();
        });

        modules.forEach((module) => {
            module.querySelector('[data-permission-select-module]')?.addEventListener('click', () => {
                const moduleCheckboxes = [...module.querySelectorAll('[data-permission-checkbox]')];
                const selectAll = moduleCheckboxes.some((checkbox) => !checkbox.checked);

                moduleCheckboxes.forEach((checkbox) => {
                    checkbox.checked = selectAll;
                });
                update();
            });
        });

        matrix.dataset.permissionMatrixInitialized = '1';
        update();
    });
}

function initLoginForm(scope = document) {
    const form = scope.querySelector('[data-login-form]');

    if (!form || form.dataset.loginInitialized === '1') {
        return;
    }

    const password = form.querySelector('#password');
    const passwordToggle = form.querySelector('[data-password-toggle]');
    const passwordIcon = form.querySelector('[data-password-toggle-icon]');
    const submit = form.querySelector('[data-login-submit]');
    const submitLabel = form.querySelector('[data-login-submit-label]');
    const spinner = form.querySelector('[data-login-spinner]');
    const arrow = form.querySelector('[data-login-arrow]');

    passwordToggle?.addEventListener('click', () => {
        const showing = password?.type === 'text';

        if (password) {
            password.type = showing ? 'password' : 'text';
            password.focus({ preventScroll: true });
        }

        passwordToggle.setAttribute('aria-label', showing ? 'Mostrar contrasena' : 'Ocultar contrasena');
        passwordIcon?.classList.toggle('ti-eye', showing);
        passwordIcon?.classList.toggle('ti-eye-off', !showing);
    });

    form.addEventListener('submit', () => {
        if (submit) {
            submit.disabled = true;
        }

        form.setAttribute('aria-busy', 'true');
        spinner?.classList.remove('d-none');
        arrow?.classList.add('d-none');

        if (submitLabel) {
            submitLabel.textContent = 'Verificando acceso...';
        }
    });

    form.dataset.loginInitialized = '1';
}

function initUserDropdowns(scope = document) {
    scope.querySelectorAll('[data-user-dropdown-toggle]').forEach((toggle) => {
        if (toggle.dataset.dropdownInitialized === '1') {
            return;
        }

        const dropdown = bootstrap.Dropdown.getOrCreateInstance(toggle, {
            autoClose: true,
            popperConfig: {
                strategy: 'fixed',
            },
        });

        toggle.addEventListener('click', (event) => {
            event.preventDefault();
            dropdown.toggle();
        });

        toggle.dataset.dropdownInitialized = '1';
    });
}

function initSidebarToggle() {
    const toggle = document.querySelector('[data-sidebar-toggle]');
    const sidebar = document.querySelector('.app-sidebar');

    if (!toggle || toggle.dataset.sidebarToggleInitialized === '1') {
        return;
    }

    const icon = toggle.querySelector('i');
    document.querySelectorAll('.app-sidebar .nav-link, .app-sidebar .app-menu-toggle').forEach((item) => {
        const label = item.querySelector('.nav-link-title')?.textContent?.trim();

        if (label && !item.getAttribute('title')) {
            item.setAttribute('title', label);
        }
    });

    const syncState = () => {
        const collapsed = document.body.classList.contains('app-sidebar-collapsed');
        toggle.setAttribute('aria-label', collapsed ? 'Expandir menu' : 'Replegar menu');
        toggle.setAttribute('title', collapsed ? 'Expandir menu' : 'Replegar menu');

        if (icon) {
            icon.className = collapsed ? 'ti ti-layout-sidebar-left-expand' : 'ti ti-layout-sidebar-left-collapse';
        }
    };

    toggle.addEventListener('click', () => {
        document.body.classList.toggle('app-sidebar-collapsed');
        document.body.classList.remove('app-sidebar-peek');
        localStorage.setItem('app-sidebar-collapsed', document.body.classList.contains('app-sidebar-collapsed') ? '1' : '0');
        syncState();
    });

    const openPeek = () => {
        if (document.body.classList.contains('app-sidebar-collapsed')) {
            document.body.classList.add('app-sidebar-peek');
        }
    };

    const closePeek = () => {
        document.body.classList.remove('app-sidebar-peek');
    };

    sidebar?.addEventListener('click', (event) => {
        if (!document.body.classList.contains('app-sidebar-collapsed')) {
            return;
        }

        openPeek();

        const link = event.target.closest('a.nav-link');
        const toggleButton = event.target.closest('.app-menu-toggle');

        if (link && !toggleButton) {
            closePeek();
        }
    }, true);

    document.addEventListener('click', (event) => {
        if (!document.body.classList.contains('app-sidebar-peek')) {
            return;
        }

        if (event.target.closest('.app-sidebar') || event.target.closest('[data-sidebar-toggle]')) {
            return;
        }

        closePeek();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closePeek();
        }
    });

    syncState();
    toggle.dataset.sidebarToggleInitialized = '1';
}

function initializeUi(scope = document) {
    disableBusinessFormAutocomplete(scope);
    initTomSelects(scope);
    initAdminDataTables(scope);
    initCharacterCounters(scope);
    initPermissionMatrices(scope);
    initLoginForm(scope);
    initImagePickers(scope);
    initUserDropdowns(scope);
}

showInitialAlerts();
initializeUi();
initSidebarToggle();

document.addEventListener('click', (event) => {
    const modalTrigger = event.target.closest('[data-modal-url]');

    if (modalTrigger) {
        event.preventDefault();
        openAjaxModal(modalTrigger);

        return;
    }

    const autoSubmitField = event.target.closest('[data-auto-submit-form]');

    if (autoSubmitField) {
        autoSubmitField.closest('form')?.requestSubmit();
    }
});

document.addEventListener('submit', (event) => {
    const ajaxForm = event.target.closest('[data-ajax-form]');
    const deleteForm = event.target.closest('[data-confirm-delete]');

    if (deleteForm) {
        event.preventDefault();
        confirmDelete(deleteForm);

        return;
    }

    if (ajaxForm) {
        event.preventDefault();
        submitAjaxForm(ajaxForm);
    }
});
