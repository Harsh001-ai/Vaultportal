<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= \App\Session::getCsrfToken() ?>">
    <title><?= htmlspecialchars($title ?? 'Workspace') ?></title>
    <!-- Modern Inter Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- UI/UX Pro Max Application Styles -->
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="app-wrapper">
    <!-- Mobile Sidebar Overlay -->
    <div id="sidebar-overlay" class="sidebar-overlay"></div>

    <!-- Application Sidebar -->
    <aside class="app-sidebar">
        <div class="sidebar-header">
            <div class="brand-badge">
                <a href="/" style="display: flex; align-items: center; gap: 0.75rem; text-decoration: none; color: inherit;">
                    <img src="/assets/images/logo.jpg" alt="Workspace Logo" class="brand-logo-img h-9 w-auto object-contain" onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='inline-flex';" />
                    <span class="brand-icon" style="display: none;">
                        <?= \App\Helpers\Icon::render('vault', '', 20) ?>
                    </span>
                    <span class="brand-name">Workspace</span>
                </a>
            </div>
            <button id="sidebar-toggle" style="background: none; border: none; color: #94a3b8; cursor: pointer; display: none;" aria-label="Toggle Menu">
                <?= \App\Helpers\Icon::render('x', '', 18) ?>
            </button>
        </div>

        <nav class="sidebar-nav">
            <?php $currentUser = \App\Services\AuthService::currentUser(); ?>
            <?php if ($currentUser && $currentUser['role'] === 'admin'): ?>
                <div class="nav-section-title">Administration</div>
                <a href="/admin/dashboard" class="nav-link-item <?= (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/admin/dashboard')) ? 'active' : '' ?>">
                    <?= \App\Helpers\Icon::render('dashboard') ?>
                    <span>Admin Overview</span>
                </a>
                <a href="/admin/assets" class="nav-link-item <?= (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/admin/assets')) ? 'active' : '' ?>">
                    <?= \App\Helpers\Icon::render('files') ?>
                    <span>All Assets & Select</span>
                </a>
                <a href="/admin/exports" class="nav-link-item <?= (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/admin/exports')) ? 'active' : '' ?>">
                    <?= \App\Helpers\Icon::render('package') ?>
                    <span>Export Center</span>
                </a>
                <a href="/admin/audit-log" class="nav-link-item <?= (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/admin/audit-log')) ? 'active' : '' ?>">
                    <?= \App\Helpers\Icon::render('history') ?>
                    <span>Download History</span>
                </a>
                <a href="/admin/users" class="nav-link-item <?= (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/admin/users')) ? 'active' : '' ?>">
                    <?= \App\Helpers\Icon::render('users') ?>
                    <span>Users & Quotas</span>
                </a>
                <a href="/admin/settings" class="nav-link-item <?= (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/admin/settings')) ? 'active' : '' ?>">
                    <?= \App\Helpers\Icon::render('settings') ?>
                    <span>System Settings</span>
                </a>

                <div class="nav-section-title" style="margin-top: 0.5rem;">User Workspace</div>
            <?php endif; ?>

            <a href="/dashboard" class="nav-link-item <?= ($_SERVER['REQUEST_URI'] ?? '') === '/dashboard' ? 'active' : '' ?>">
                <?= \App\Helpers\Icon::render('dashboard') ?>
                <span>My Dashboard</span>
            </a>
            <a href="/assets" class="nav-link-item <?= ($_SERVER['REQUEST_URI'] ?? '') === '/assets' ? 'active' : '' ?>">
                <?= \App\Helpers\Icon::render('files') ?>
                <span>My Assets</span>
            </a>
            <a href="/collections" class="nav-link-item <?= (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/collections')) ? 'active' : '' ?>">
                <?= \App\Helpers\Icon::render('folder') ?>
                <span>Collections</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="user-info-pill">
                <div class="user-avatar">
                    <?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?>
                </div>
                <div class="user-details">
                    <div class="user-name"><?= htmlspecialchars($currentUser['name'] ?? 'User') ?></div>
                    <div class="user-role-badge"><?= htmlspecialchars($currentUser['role'] ?? 'user') ?></div>
                </div>
            </div>
            <a href="/logout" title="Sign Out" style="color: #94a3b8; display: flex; align-items: center; padding: 0.25rem;">
                <?= \App\Helpers\Icon::render('log-out', '', 18) ?>
            </a>
        </div>
    </aside>

    <!-- Main Content Application -->
    <div class="app-main">
        <header class="app-header">
            <div class="header-left">
                <button type="button" id="mobile-menu-btn" class="mobile-menu-btn" aria-label="Open Navigation Menu">
                    <?= \App\Helpers\Icon::render('menu', '', 20) ?>
                </button>
                <div class="header-search">
                    <?= \App\Helpers\Icon::render('search') ?>
                    <input type="text" id="global-search" placeholder="Search assets, users, files..." />
                </div>
            </div>
            <div class="header-right">
                <span class="badge-vault <?= ($currentUser['role'] ?? '') === 'admin' ? 'badge-admin' : 'badge-user' ?>">
                    <?= \App\Helpers\Icon::render('shield', '', 12) ?>
                    <span><?= strtoupper($currentUser['role'] ?? 'USER') ?> ROLE</span>
                </span>
                <a href="/logout" class="btn-vault btn-secondary btn-sm" style="text-decoration: none;">
                    <?= \App\Helpers\Icon::render('log-out', '', 14) ?>
                    <span>Sign Out</span>
                </a>
            </div>
        </header>

        <main class="content-body w-full">
            <div class="w-full min-h-screen max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Toast notification messages -->
                <?php if (!empty($success)): ?>
                    <div style="background: var(--color-success-subtle); border-left: 4px solid var(--color-success); border-radius: var(--border-radius-sm); padding: 0.85rem 1.25rem; margin-bottom: 1.25rem; font-size: 0.875rem; color: #065f46; display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <?= \App\Helpers\Icon::render('check-circle', '', 18) ?>
                            <span><?= htmlspecialchars($success) ?></span>
                        </div>
                        <button type="button" style="background: none; border: none; cursor: pointer; color: #065f46;" onclick="this.parentElement.remove()">✕</button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div style="background: var(--color-danger-subtle); border-left: 4px solid var(--color-danger); border-radius: var(--border-radius-sm); padding: 0.85rem 1.25rem; margin-bottom: 1.25rem; font-size: 0.875rem; color: #991b1b; display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <?= \App\Helpers\Icon::render('alert-triangle', '', 18) ?>
                            <span><?= htmlspecialchars($error) ?></span>
                        </div>
                        <button type="button" style="background: none; border: none; cursor: pointer; color: #991b1b;" onclick="this.parentElement.remove()">✕</button>
                    </div>
                <?php endif; ?>

                <?= $content ?>
            </div>
        </main>
    </div>
</div>

<!-- Global Export Wizard Modal -->
<div id="export-wizard-modal" class="modal-backdrop-vault">
    <div class="modal-box-vault" style="max-width: 680px;">
        <div class="modal-header-vault">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <div class="brand-icon" style="width: 28px; height: 28px;">
                    <?= \App\Helpers\Icon::render('package', '', 16) ?>
                </div>
                <div style="font-weight: 700; font-size: 1.05rem;">Export & Archive Wizard</div>
            </div>
            <button type="button" style="background: none; border: none; cursor: pointer; color: #94a3b8;" data-close-modal>
                <?= \App\Helpers\Icon::render('x', '', 18) ?>
            </button>
        </div>
        <div class="modal-body-vault" id="export-wizard-content">
            <!-- Dynamic review & options rendered by export.js -->
        </div>
        <div class="modal-footer-vault" id="export-wizard-footer">
            <button type="button" class="btn-vault btn-secondary" data-close-modal>Cancel</button>
        </div>
    </div>
</div>

<!-- Global Scripts -->
<script src="/assets/js/app.js"></script>
<script src="/assets/js/uploader.js"></script>
<script src="/assets/js/export.js"></script>
</body>
</html>
