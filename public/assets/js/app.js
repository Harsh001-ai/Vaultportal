// public/assets/js/app.js
// Enterprise Workspace Core Utilities

window.Vault = {
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',

    // Universal fetch wrapper with automatic CSRF & JSON handling
    async api(url, options = {}) {
        const headers = {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': this.csrfToken,
            ...options.headers
        };

        if (options.body && typeof options.body === 'object' && !(options.body instanceof FormData)) {
            headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(options.body);
        }

        try {
            const res = await fetch(url, { ...options, headers });
            const data = await res.json();
            if (!res.ok) {
                throw new Error(data.message || 'Server request failed');
            }
            return data;
        } catch (err) {
            console.error('API Error:', err);
            throw err;
        }
    },

    // Toast notification
    toast(message, type = 'info') {
        let container = document.getElementById('vault-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'vault-toast-container';
            container.className = 'toast-container-vault';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `toast-vault toast-${type}`;
        
        const colors = {
            success: '#10b981',
            danger: '#ef4444',
            warning: '#f59e0b',
            info: '#4f46e5'
        };

        toast.style.borderLeft = `4px solid ${colors[type] || colors.info}`;
        toast.innerHTML = `
            <div style="flex: 1;">
                <div style="font-weight: 600; font-size: 0.8rem; text-transform: uppercase; color: ${colors[type] || colors.info};">${type}</div>
                <div>${message}</div>
            </div>
            <button type="button" style="background: none; border: none; cursor: pointer; color: #94a3b8;" onclick="this.parentElement.remove()">✕</button>
        `;

        container.appendChild(toast);
        setTimeout(() => {
            if (toast.parentElement) {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 200ms ease';
                setTimeout(() => toast.remove(), 200);
            }
        }, 4000);
    },

    // Modal Manager
    openModal(modalId) {
        const el = document.getElementById(modalId);
        if (el) {
            el.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    },

    closeModal(modalId) {
        const el = document.getElementById(modalId);
        if (el) {
            el.classList.remove('show');
            document.body.style.overflow = '';
        }
    },

    // Format bytes to human readable
    formatBytes(bytes, decimals = 1) {
        if (!+bytes) return '0 B';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
    }
};

// Global DOM ready listener
document.addEventListener('DOMContentLoaded', () => {
    // Mobile sidebar toggle
    const toggleBtn = document.getElementById('sidebar-toggle');
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const sidebar = document.querySelector('.app-sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    const toggleSidebar = () => {
        if (!sidebar) return;
        const isOpen = sidebar.classList.toggle('open');
        if (overlay) overlay.classList.toggle('show', isOpen);
        document.body.style.overflow = (isOpen && window.innerWidth <= 768) ? 'hidden' : '';
    };

    if (toggleBtn) toggleBtn.addEventListener('click', toggleSidebar);
    if (mobileMenuBtn) mobileMenuBtn.addEventListener('click', toggleSidebar);

    if (overlay) {
        overlay.addEventListener('click', () => {
            sidebar?.classList.remove('open');
            overlay.classList.remove('show');
        });
    }

    // Modal close listeners
    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const modal = e.target.closest('.modal-backdrop-vault');
            if (modal) {
                modal.classList.remove('show');
                document.body.style.overflow = '';
            }
        });
    });
});
