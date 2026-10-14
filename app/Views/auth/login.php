<h2 style="font-size: 1.15rem; font-weight: 700; margin: 0 0 1.25rem; color: var(--text-main);">Sign in to your portal</h2>

<?php if (!empty($error)): ?>
    <div style="background: var(--color-danger-subtle); border: 1px solid var(--color-danger-border); color: #991b1b; padding: 0.75rem 1rem; border-radius: var(--border-radius-sm); font-size: 0.85rem; margin-bottom: 1.25rem;">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div style="background: var(--color-success-subtle); border: 1px solid var(--color-success-border); color: #065f46; padding: 0.75rem 1rem; border-radius: var(--border-radius-sm); font-size: 0.85rem; margin-bottom: 1.25rem;">
        <?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>

<form action="/login" method="POST">
    <input type="hidden" name="_csrf_token" value="<?= \App\Session::getCsrfToken() ?>">

    <div style="margin-bottom: 1rem;">
        <label for="email" style="display: block; font-size: 0.825rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--text-main);">Email Address</label>
        <input type="email" id="email" name="email" required autofocus placeholder="name@company.com" style="width: 100%; padding: 0.6rem 0.85rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.9rem;" />
    </div>

    <div style="margin-bottom: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
            <label for="password" style="font-size: 0.825rem; font-weight: 600; color: var(--text-main);">Password</label>
        </div>
        <input type="password" id="password" name="password" required placeholder="••••••••" style="width: 100%; padding: 0.6rem 0.85rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.9rem;" />
    </div>

    <button type="submit" class="btn-vault btn-primary" style="width: 100%; padding: 0.7rem; font-size: 0.95rem;">
        Sign In
    </button>
</form>

<div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--border-light); font-size: 0.825rem; text-align: center; color: var(--text-muted);">
    Don't have an account? <a href="/register" style="font-weight: 600;">Register here</a>
</div>
