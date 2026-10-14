<div style="text-align: center; padding: 1rem 0;">
    <div style="width: 54px; height: 54px; border-radius: 50%; background: var(--color-warning-subtle); color: var(--color-warning); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
        <?= \App\Helpers\Icon::render('alert-triangle', '', 28) ?>
    </div>
    <h2 style="font-size: 1.25rem; font-weight: 700; margin: 0 0 0.5rem; color: var(--text-main);">Rate Limit Exceeded</h2>
    <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0 0 1.5rem;">
        Too many failed or rapid requests have been received from your network. For security, please wait <strong><?= htmlspecialchars($remaining ?? 900) ?> seconds</strong> before retrying.
    </p>
    <a href="/login" class="btn-vault btn-secondary" style="text-decoration: none;">Try Again</a>
</div>
