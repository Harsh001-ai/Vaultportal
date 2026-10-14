<h2 style="font-size: 1.15rem; font-weight: 700; margin: 0 0 1.25rem; color: var(--text-main);">Create Client Account</h2>

<?php if (!empty($error)): ?>
    <div style="background: var(--color-danger-subtle); border: 1px solid var(--color-danger-border); color: #991b1b; padding: 0.75rem 1rem; border-radius: var(--border-radius-sm); font-size: 0.85rem; margin-bottom: 1.25rem;">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<form action="/register" method="POST">
    <input type="hidden" name="_csrf_token" value="<?= \App\Session::getCsrfToken() ?>">

    <div style="margin-bottom: 1rem;">
        <label for="name" style="display: block; font-size: 0.825rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--text-main);">Full Name</label>
        <input type="text" id="name" name="name" required placeholder="e.g. Jane Doe" style="width: 100%; padding: 0.6rem 0.85rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.9rem;" />
    </div>

    <div style="margin-bottom: 1rem;">
        <label for="email" style="display: block; font-size: 0.825rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--text-main);">Email Address</label>
        <input type="email" id="email" name="email" required placeholder="name@company.com" style="width: 100%; padding: 0.6rem 0.85rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.9rem;" />
    </div>

    <div style="margin-bottom: 1rem;">
        <label for="password" style="display: block; font-size: 0.825rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--text-main);">Password</label>
        <input type="password" id="password" name="password" required minlength="8" placeholder="At least 8 characters" style="width: 100%; padding: 0.6rem 0.85rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.9rem;" />
    </div>

    <div style="margin-bottom: 1.5rem;">
        <label for="password_confirmation" style="display: block; font-size: 0.825rem; font-weight: 600; margin-bottom: 0.35rem; color: var(--text-main);">Confirm Password</label>
        <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8" placeholder="Repeat your password" style="width: 100%; padding: 0.6rem 0.85rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.9rem;" />
    </div>

    <button type="submit" class="btn-vault btn-primary" style="width: 100%; padding: 0.7rem; font-size: 0.95rem;">
        Register & Enter Vault
    </button>
</form>

<div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--border-light); font-size: 0.825rem; text-align: center; color: var(--text-muted);">
    Already have an account? <a href="/login" style="font-weight: 600;">Sign in here</a>
</div>
