<?php
// footer.php
?>
</main>
</div> <!-- /.app-shell -->

<!-- Bootstrap JS -->
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
    crossorigin="anonymous"
></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const body = document.body;
        const backdrop = document.getElementById('sidebarBackdrop');
        const mobileBtn = document.getElementById('mobileMenuBtn');
        const collapseBtn = document.getElementById('collapseSidebarBtn');
        const accordions = document.querySelectorAll('.menu-accordion');
        const menuLinks = document.querySelectorAll('.menu-link[data-close-drawer="true"]');
        const sidebar = document.getElementById('appSidebar');
        const flyout = document.getElementById('menuFlyout');
        let currentFlyoutGroup = null;

        const closeDrawer = () => body.classList.remove('sidebar-open');
        const isCollapsed = () => body.classList.contains('sidebar-collapsed');
        const closeFlyout = () => {
            if (!flyout) return;
            flyout.classList.remove('visible');
            flyout.innerHTML = '';
            currentFlyoutGroup = null;
            document.querySelectorAll('.menu-group').forEach(el => el.classList.remove('open'));
        };
        const openFlyout = (group) => {
            if (!flyout || !group) return;
            const submenu = group.querySelector('.submenu');
            if (!submenu) return;

            const clone = submenu.cloneNode(true);
            clone.style.display = 'block';

            const title = group.dataset.menuTitle || '';
            flyout.innerHTML = '';
            if (title) {
                const header = document.createElement('div');
                header.className = 'menu-flyout-header';
                header.textContent = title;
                flyout.appendChild(header);
            }
            flyout.appendChild(clone);

            const rect = sidebar?.getBoundingClientRect();
            const itemRect = group.getBoundingClientRect();
            const headerHeight = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--header-height')) || 56;
            if (rect) {
                const left = rect.right;
                const top = Math.max(headerHeight, Math.min(itemRect.top, window.innerHeight - 260));
                flyout.style.left = `${left}px`;
                flyout.style.top = `${top}px`;
                flyout.style.maxHeight = `calc(100vh - ${headerHeight}px)`;
            }

            document.querySelectorAll('.menu-group').forEach(el => el.classList.remove('open'));
            group.classList.add('open');

            flyout.classList.add('visible');
            currentFlyoutGroup = group;
        };

        mobileBtn?.addEventListener('click', () => body.classList.add('sidebar-open'));
        collapseBtn?.addEventListener('click', () => {
            closeFlyout();
            body.classList.toggle('sidebar-collapsed');
        });
        backdrop?.addEventListener('click', closeDrawer);

        accordions.forEach(btn => {
            btn.addEventListener('click', () => {
                const group = btn.closest('.menu-group');
                if (!group) {
                    return;
                }
                const collapsed = isCollapsed() && !body.classList.contains('sidebar-open');
                if (collapsed) {
                    openFlyout(group); // Flyout keeps submenu outside the narrow rail.
                    return;
                }

                const isOpen = group.classList.contains('open');
                document.querySelectorAll('.menu-group.has-children').forEach(el => {
                    if (el !== group) {
                        el.classList.remove('open');
                    }
                });
                if (isOpen) {
                    group.classList.remove('open');
                } else {
                    group.classList.add('open');
                }
            });
        });

        menuLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 768) {
                    closeDrawer();
                }
                closeFlyout();
            });
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) {
                body.classList.remove('sidebar-open');
            }
            closeFlyout();
        });

        document.addEventListener('click', (ev) => {
            if (!flyout || !flyout.classList.contains('visible')) return;
            const target = ev.target;
            if (!(flyout.contains(target) || sidebar?.contains(target))) {
                closeFlyout();
            }
        });

        document.addEventListener('keydown', (ev) => {
            if (ev.key === 'Escape') {
                closeFlyout();
                closeDrawer();
            }
        });

        flyout?.addEventListener('click', (ev) => {
            const link = ev.target.closest('a.menu-link');
            if (link) {
                closeFlyout();
                if (window.innerWidth < 768) {
                    closeDrawer();
                }
            }
        });
    });
</script>
<script src="assets/js/toast.js"></script>
<script>
    (function () {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        if (!csrfToken) {
            return;
        }

        document.addEventListener('submit', function (event) {
            const form = event.target;
            if (!form || form.tagName !== 'FORM') {
                return;
            }
            if ((form.getAttribute('method') || 'GET').toUpperCase() !== 'POST') {
                return;
            }
            if (!form.querySelector('input[name="_csrf"]')) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = '_csrf';
                input.value = csrfToken;
                form.appendChild(input);
            }
        }, true);

        if (window.fetch && !window.__csrfFetchPatched) {
            const originalFetch = window.fetch.bind(window);
            window.fetch = function (resource, options = {}) {
                const method = (options.method || 'GET').toUpperCase();
                if (method !== 'POST') {
                    return originalFetch(resource, options);
                }

                const headers = new Headers(options.headers || {});
                if (!headers.has('X-CSRF-Token')) {
                    headers.set('X-CSRF-Token', csrfToken);
                }

                return originalFetch(resource, {
                    ...options,
                    headers,
                });
            };
            window.__csrfFetchPatched = true;
        }
    })();
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const trigger = document.getElementById('changeCompanyTrigger');
        const modalEl = document.getElementById('companySwitchModal');
        const listEl = document.getElementById('companySwitchList');
        const messageEl = document.getElementById('companySwitchMessage');
        const saveBtn = document.getElementById('companySwitchSave');
        let companies = [];

        if (!trigger || !modalEl || !listEl || !saveBtn || !window.bootstrap) {
            return;
        }

        const modal = new bootstrap.Modal(modalEl);

        const renderCompanies = (items, currentId) => {
            listEl.innerHTML = '';
            messageEl.textContent = '';

            if (!items.length) {
                messageEl.textContent = 'No hay empresas asignadas.';
                return;
            }

            if (items.length === 1) {
                messageEl.textContent = 'No hay otras empresas asignadas.';
            }

            items.forEach((empresa, index) => {
                const empresaId = parseInt(empresa.empresaid || empresa.empresaId || 0, 10);
                const empresaNombre = empresa.razonsocial || empresa.empresaNombre || `Empresa ${empresaId}`;
                const safeNombre = window.ToastManager ? window.ToastManager.escape(empresaNombre) : empresaNombre;

                const wrapper = document.createElement('label');
                wrapper.className = 'form-check d-flex align-items-center gap-2';
                wrapper.innerHTML = `
                    <input class="form-check-input" type="radio" name="empresa_switch" value="${empresaId}">
                    <span class="form-check-label">${safeNombre}</span>
                `;
                const input = wrapper.querySelector('input');
                if (empresaId === currentId || (currentId === 0 && index === 0)) {
                    input.checked = true;
                }
                listEl.appendChild(wrapper);
            });
        };

        const loadCompanies = async () => {
            const currentId = parseInt(trigger.dataset.currentCompanyId || '0', 10);
            messageEl.textContent = 'Cargando empresas...';
            listEl.innerHTML = '';
            try {
                const response = await fetch('?route=companies/list-for-change', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const payload = await response.json();
                if (!response.ok || payload.status !== 200) {
                    throw new Error(payload.message || 'No se pudo cargar empresas.');
                }
                companies = payload.data || [];
                renderCompanies(companies, payload.currentEmpresaId || currentId);
            } catch (error) {
                messageEl.textContent = error.message || 'No se pudo cargar empresas.';
            }
        };

        trigger.addEventListener('click', () => {
            loadCompanies();
        });

        saveBtn.addEventListener('click', async () => {
            const selected = modalEl.querySelector('input[name="empresa_switch"]:checked');
            const empresaId = selected ? parseInt(selected.value, 10) : 0;
            if (!empresaId) {
                if (window.ToastManager) {
                    ToastManager.show('Debe seleccionar una empresa.', 'warning');
                }
                return;
            }

            try {
                const formData = new FormData();
                formData.append('empresaid', empresaId);

                const response = await fetch('?route=empresas/cambiar-empresa', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                const payload = await response.json();
                if (!response.ok || payload.status !== 200) {
                    throw new Error(payload.message || 'No se pudo cambiar empresa.');
                }

                if (window.ToastManager) {
                    ToastManager.show(payload.message || 'Empresa actualizada.', 'success');
                }
                modal.hide();
                window.location.reload();
            } catch (error) {
                if (window.ToastManager) {
                    ToastManager.show(error.message || 'No se pudo cambiar empresa.', 'danger');
                }
            }
        });
    });
</script>
<?php require __DIR__ . '/partials/modal_confirm.php'; ?>
<script src="assets/js/confirm-modal.js"></script>
<?php
require_once dirname(__DIR__, 2) . '/src/Helpers/FlashMessageHelper.php';
$toastData = FlashMessageHelper::pullToast();
if (!empty($toastData['message'])):
    $toastMessage = json_encode($toastData['message'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    $toastType    = json_encode($toastData['type'] ?? 'info', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
?>
<div id="toast-fallback" class="alert alert-info d-none" role="alert"></div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Flag to let other scripts know a toast is present on this load
        window.__hasToast = true;
        const msg = <?php echo $toastMessage; ?>;
        const type = <?php echo $toastType; ?>;
        if (window.ToastManager) {
            ToastManager.show(msg, type);
        } else {
            const fallback = document.getElementById('toast-fallback');
            if (fallback) {
                fallback.textContent = msg;
                fallback.classList.remove('d-none');
                fallback.classList.remove('alert-info', 'alert-success', 'alert-danger', 'alert-warning');
                fallback.classList.add('alert-' + (type || 'info'));
            }
        }
    });
</script>
<?php endif; ?>

</body>
</html>
