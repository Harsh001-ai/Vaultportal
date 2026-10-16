<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= \App\Session::getCsrfToken() ?>">
    <title><?= htmlspecialchars($title ?? 'Workspace') ?></title>
    <!-- Immediate Theme Initialization (No FOUC) -->
    <script>
        (function() {
            try {
                var saved = localStorage.getItem('workspace_theme');
                var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                var theme = saved || (prefersDark ? 'dark' : 'light');
                document.documentElement.setAttribute('data-theme', theme);
            } catch (e) {}
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css?v=2.2">
</head>
<body style="background-color: var(--bg-app); display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 1.5rem;">

<div style="width: 100%; max-width: 440px;">
    <div style="text-align: center; margin-bottom: 2rem;">
        <div style="display: flex; justify-content: center; margin-bottom: 1rem;">
            <img src="/assets/images/logo.jpg" alt="Workspace Logo" class="brand-logo-img h-12 w-auto object-contain" height="48" style="height: 48px !important; max-height: 48px !important; width: auto !important; max-width: 160px !important; object-fit: contain !important; display: block; margin: 0 auto;" onerror="this.onerror=null; this.style.display='none'; document.getElementById('auth-fallback-icon').style.display='inline-flex';" />
            <div id="auth-fallback-icon" class="brand-icon" style="display: none; width: 44px; height: 44px; border-radius: var(--border-radius-md);">
                <?= \App\Helpers\Icon::render('vault', '', 24) ?>
            </div>
        </div>
        <h1 style="font-size: 1.35rem; font-weight: 700; margin: 0; color: var(--text-main); letter-spacing: -0.02em;">Workspace</h1>
        <p style="font-size: 0.825rem; color: var(--text-muted); margin: 0.25rem 0 0;">Secure Digital Asset Collection & Delivery System</p>
    </div>

    <div class="card-surface" style="padding: 2rem; box-shadow: var(--shadow-lg);">
        <?= $content ?>
    </div>

    <div style="text-align: center; margin-top: 1.5rem; font-size: 0.75rem; color: var(--text-muted);">
        Encrypted TLS File Transfer • Zero-Trust Access Control • Immutable Audit Logs
    </div>
</div>

<script src="/assets/js/app.js?v=2.2"></script>
</body>
</html>
