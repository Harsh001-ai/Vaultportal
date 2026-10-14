<div style="text-align: center; padding: 1rem 0;">
    <div style="width: 54px; height: 54px; border-radius: 50%; background: var(--color-danger-subtle); color: var(--color-danger); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
        <?= \App\Helpers\Icon::render('shield-alert', '', 28) ?>
    </div>
    <h2 style="font-size: 1.25rem; font-weight: 700; margin: 0 0 0.5rem; color: var(--text-main);">403 — Access Denied</h2>
    <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0 0 1.5rem;">
        <?= htmlspecialchars($message ?? 'You do not have administrative permissions to view or execute actions on this resource.') ?>
    </p>
    <a href="/dashboard" class="btn-vault btn-primary" style="text-decoration: none;">Return to Dashboard</a>
</div>
