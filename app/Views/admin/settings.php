<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <div>
        <h1 style="font-size: 1.4rem; font-weight: 700; margin: 0; color: var(--text-main);">System Settings & Security Policies</h1>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0.25rem 0 0;">Configure global portal parameters, storage limits, and security filters.</p>
    </div>
</div>

<div class="card-surface" style="padding: 1.5rem; max-width: 720px;">
    <form action="/admin/settings" method="POST">
        <input type="hidden" name="_csrf_token" value="<?= \App\Session::getCsrfToken() ?>">

        <div style="margin-bottom: 1.25rem;">
            <label for="app_name" style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 0.35rem; color: var(--text-main);">Portal Brand Name</label>
            <input type="text" id="app_name" name="app_name" value="<?= htmlspecialchars($settings['app_name'] ?? 'Workspace') ?>" style="width: 100%; padding: 0.55rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.9rem;" />
        </div>

        <div style="margin-bottom: 1.25rem;">
            <label for="allowed_extensions" style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 0.35rem; color: var(--text-main);">Allowed File Extensions (Comma-separated)</label>
            <input type="text" id="allowed_extensions" name="allowed_extensions" value="<?= htmlspecialchars($settings['allowed_extensions'] ?? 'jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip') ?>" style="width: 100%; padding: 0.55rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.85rem; font-family: monospace;" />
            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">Dangerous extensions (.php, .exe, .sh, .bat) are always unconditionally blocked regardless of this setting.</div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
            <div>
                <label for="max_upload_size_mb" style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 0.35rem; color: var(--text-main);">Max Upload Size (MB)</label>
                <input type="number" id="max_upload_size_mb" name="max_upload_size_mb" value="<?= htmlspecialchars($settings['max_upload_size_mb'] ?? '100') ?>" min="1" max="1024" style="width: 100%; padding: 0.55rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.9rem;" />
            </div>
            <div>
                <label for="export_expiry_hours" style="font-size: 0.85rem; font-weight: 600; display: block; margin-bottom: 0.35rem; color: var(--text-main);">Export Archive Expiration (Hours)</label>
                <input type="number" id="export_expiry_hours" name="export_expiry_hours" value="<?= htmlspecialchars($settings['export_expiry_hours'] ?? '24') ?>" min="1" max="720" style="width: 100%; padding: 0.55rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.9rem;" />
            </div>
        </div>

        <div style="margin-bottom: 1.5rem; padding: 1rem; background: var(--bg-subtle); border: 1px solid var(--border-light); border-radius: var(--border-radius-sm);">
            <label style="display: flex; align-items: flex-start; gap: 0.75rem; cursor: pointer;">
                <input type="checkbox" name="quarantine_enabled" value="1" <?= (($settings['quarantine_enabled'] ?? '1') === '1') ? 'checked' : '' ?> style="margin-top: 0.2rem;" />
                <div>
                    <div style="font-weight: 600; font-size: 0.875rem; color: var(--text-main);">Enable Heuristic Malware & Executable Quarantine</div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.2rem;">
                        Inspects uploaded files for embedded PHP code, PE executable headers, and script injection. Quarantined assets are locked from distribution until reviewed.
                    </div>
                </div>
            </label>
        </div>

        <div style="margin-bottom: 1.5rem; padding: 1.25rem; background: var(--bg-subtle); border: 1px solid var(--border-light); border-radius: var(--border-radius-sm);">
            <div style="font-weight: 600; font-size: 0.875rem; color: var(--text-main); margin-bottom: 0.25rem;">Interface Theme & Appearance</div>
            <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.85rem;">Select your preferred visual mode for the Workspace panel. Your choice is saved automatically.</div>
            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                <button type="button" class="btn-vault btn-secondary btn-sm" data-set-theme="light" data-theme-choice="light">
                    <?= \App\Helpers\Icon::render('sun', '', 14) ?>
                    <span>Light Mode</span>
                </button>
                <button type="button" class="btn-vault btn-secondary btn-sm" data-set-theme="dark" data-theme-choice="dark">
                    <?= \App\Helpers\Icon::render('moon', '', 14) ?>
                    <span>Dark Mode</span>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-vault btn-primary">
            Save System Settings
        </button>
    </form>
</div>
