<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <div>
        <h1 style="font-size: 1.4rem; font-weight: 700; margin: 0; color: var(--text-main);">Collections & Categories</h1>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0.25rem 0 0;">Group your uploaded assets into organized departmental folders.</p>
    </div>
    <button type="button" class="btn-vault btn-primary" onclick="Vault.openModal('create-coll-modal')">
        <?= \App\Helpers\Icon::render('folder') ?>
        <span>New Collection</span>
    </button>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.25rem;">
    <?php if (empty($collections)): ?>
        <div class="card-surface" style="grid-column: 1 / -1; padding: 3rem; text-align: center; color: var(--text-muted);">
            No collections yet. Click "New Collection" to organize your files.
        </div>
    <?php else: ?>
        <?php foreach ($collections as $c): ?>
            <div class="card-surface" style="padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                        <div class="file-icon-box document" style="background: var(--color-primary-subtle); color: var(--color-primary);">
                            <?= \App\Helpers\Icon::render('folder', '', 20) ?>
                        </div>
                        <?php if ($c['is_global']): ?>
                            <span class="badge-vault badge-admin">Enterprise Global</span>
                        <?php endif; ?>
                    </div>
                    <h3 style="font-size: 1.05rem; font-weight: 700; margin: 0 0 0.4rem; color: var(--text-main);">
                        <?= htmlspecialchars($c['name']) ?>
                    </h3>
                    <p style="font-size: 0.825rem; color: var(--text-muted); margin: 0 0 1rem; min-height: 2.5rem;">
                        <?= htmlspecialchars($c['description'] ?? 'No description provided.') ?>
                    </p>
                </div>

                <div style="border-top: 1px solid var(--border-light); padding-top: 0.85rem; display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem;">
                    <div>
                        <strong><?= $c['asset_count'] ?></strong> assets
                        <span style="color: var(--text-muted);">• <?= round($c['total_bytes'] / (1024 * 1024), 2) ?> MB</span>
                    </div>
                    <div style="display: flex; gap: 0.35rem;">
                        <a href="/assets?collection=<?= $c['id'] ?>" class="btn-vault btn-secondary btn-sm">
                            Browse
                        </a>
                        <?php if (!$c['is_global'] || $isAdmin): ?>
                            <form action="/collections/<?= $c['id'] ?>/delete" method="POST" onsubmit="return confirm('Delete collection? Assets will be kept in unorganized root.');" style="display: inline;">
                                <input type="hidden" name="_csrf_token" value="<?= \App\Session::getCsrfToken() ?>">
                                <button type="submit" class="btn-vault btn-secondary btn-sm" style="color: var(--color-danger);" title="Delete Collection">
                                    <?= \App\Helpers\Icon::render('trash', '', 14) ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Create Collection Modal -->
<div id="create-coll-modal" class="modal-backdrop-vault">
    <div class="modal-box-vault" style="max-width: 480px;">
        <form action="/collections" method="POST">
            <input type="hidden" name="_csrf_token" value="<?= \App\Session::getCsrfToken() ?>">
            <div class="modal-header-vault">
                <div style="font-weight: 700; font-size: 1.05rem;">Create New Collection</div>
                <button type="button" style="background: none; border: none; cursor: pointer; color: #94a3b8;" data-close-modal>
                    <?= \App\Helpers\Icon::render('x', '', 18) ?>
                </button>
            </div>
            <div class="modal-body-vault">
                <div style="margin-bottom: 1rem;">
                    <label for="cname" style="font-size: 0.825rem; font-weight: 600; display: block; margin-bottom: 0.35rem;">Collection Name</label>
                    <input type="text" id="cname" name="name" required placeholder="e.g. Q4 Brand Assets" style="width: 100%; padding: 0.55rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.9rem;" />
                </div>
                <div style="margin-bottom: 1rem;">
                    <label for="cdesc" style="font-size: 0.825rem; font-weight: 600; display: block; margin-bottom: 0.35rem;">Description</label>
                    <textarea id="cdesc" name="description" rows="3" placeholder="Brief summary of purpose..." style="width: 100%; padding: 0.55rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.85rem;"></textarea>
                </div>
                <?php if ($isAdmin): ?>
                    <div style="margin-bottom: 0.5rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; cursor: pointer;">
                            <input type="checkbox" name="is_global" value="1" />
                            <span>Global Enterprise Collection (Visible to all users)</span>
                        </label>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer-vault">
                <button type="button" class="btn-vault btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn-vault btn-primary">Create Collection</button>
            </div>
        </form>
    </div>
</div>
