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
    },

    // Theme Management
    getTheme() {
        return document.documentElement.getAttribute('data-theme') || 
               localStorage.getItem('workspace_theme') || 
               (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    },

    setTheme(theme) {
        if (theme !== 'dark' && theme !== 'light') {
            theme = 'light';
        }
        document.documentElement.setAttribute('data-theme', theme);
        try {
            localStorage.setItem('workspace_theme', theme);
        } catch (e) {}
        this.updateThemeUI(theme);
    },

    toggleTheme() {
        const current = this.getTheme();
        const next = current === 'dark' ? 'light' : 'dark';
        this.setTheme(next);
        this.toast(`Switched to ${next === 'dark' ? 'Dark' : 'Light'} Mode`, 'info');
    },

    updateThemeUI(theme) {
        const toggleBtn = document.getElementById('theme-toggle-btn');
        if (toggleBtn) {
            const label = toggleBtn.querySelector('.theme-label-text');
            if (label) {
                label.textContent = theme === 'dark' ? 'Dark' : 'Light';
            }
        }
        document.querySelectorAll('[data-theme-choice]').forEach(el => {
            if (el.getAttribute('data-theme-choice') === theme) {
                el.classList.add('active');
            } else {
                el.classList.remove('active');
            }
        });
    }
};

// Global DOM ready listener
document.addEventListener('DOMContentLoaded', () => {
    // Theme toggle setup
    const themeToggleBtn = document.getElementById('theme-toggle-btn');
    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            Vault.toggleTheme();
        });
    }

    document.querySelectorAll('[data-set-theme]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const targetTheme = btn.getAttribute('data-set-theme');
            Vault.setTheme(targetTheme);
            Vault.toast(`Switched to ${targetTheme === 'dark' ? 'Dark' : 'Light'} Mode`, 'info');
        });
    });

    Vault.updateThemeUI(Vault.getTheme());

    // Button micro-interactions: Form submit loading animation
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', (e) => {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.classList.contains('no-spin')) {
                if (!form.checkValidity || form.checkValidity()) {
                    submitBtn.classList.add('is-loading');
                }
            }
        });
    });

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
