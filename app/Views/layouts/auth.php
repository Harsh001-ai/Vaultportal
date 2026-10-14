<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= \App\Session::getCsrfToken() ?>">
    <title><?= htmlspecialchars($title ?? 'VaultPortal') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body style="background-color: #f1f5f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 1.5rem;">

<div style="width: 100%; max-width: 440px;">
    <div style="text-align: center; margin-bottom: 2rem;">
        <div class="brand-icon" style="width: 44px; height: 44px; margin: 0 auto 0.75rem; border-radius: var(--border-radius-md);">
            <?= \App\Helpers\Icon::render('vault', '', 24) ?>
        </div>
        <h1 style="font-size: 1.35rem; font-weight: 700; margin: 0; color: var(--text-main); letter-spacing: -0.02em;">VaultPortal Enterprise</h1>
        <p style="font-size: 0.825rem; color: var(--text-muted); margin: 0.25rem 0 0;">Secure Digital Asset Collection & Delivery System</p>
    </div>

    <div class="card-surface" style="padding: 2rem; box-shadow: var(--shadow-lg);">
        <?= $content ?>
    </div>

    <div style="text-align: center; margin-top: 1.5rem; font-size: 0.75rem; color: var(--text-muted);">
        Encrypted TLS File Transfer • Zero-Trust Access Control • Immutable Audit Logs
    </div>
</div>

<script src="/assets/js/app.js"></script>
</body>
</html>
