// ToastManager: helper para mostrar mensajes Bootstrap 5 en todo el frontend.
// Uso:
//   ToastManager.show('Guardado con exito', 'success');
//   ToastManager.show('Ocurrio un error', 'danger', 6000);
class ToastManager {
    static ensureContainer() {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            document.body.appendChild(container);
        }
        return container;
    }

    static show(message, type = 'info', delay = 4000) {
        const container = ToastManager.ensureContainer();
        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center text-bg-${ToastManager.variant(type)} border-0`;
        toastEl.role = 'alert';
        toastEl.ariaLive = 'assertive';
        toastEl.ariaAtomic = 'true';

        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${ToastManager.escape(message)}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;

        container.appendChild(toastEl);
        const toast = new bootstrap.Toast(toastEl, { delay, autohide: true });
        toast.show();
        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    }

    static variant(type) {
        const allowed = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'];
        return allowed.includes(type) ? type : 'info';
    }

    static escape(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
}

// Exponer de forma global
window.ToastManager = ToastManager;
