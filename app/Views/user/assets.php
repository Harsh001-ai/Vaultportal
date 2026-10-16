<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <div>
        <h1 style="font-size: 1.4rem; font-weight: 700; margin: 0; color: var(--text-main);">My Assets</h1>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0.25rem 0 0;">Manage your uploaded files, track security reviews, and organize collections.</p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <button type="button" class="btn-vault btn-primary" onclick="Vault.openModal('upload-modal')">
            <?= \App\Helpers\Icon::render('upload') ?>
            <span>Upload Assets</span>
        </button>
    </div>
</div>

<?php if (!empty($activeCollection)): ?>
    <!-- Active Folder Banner -->
    <div style="background: var(--color-primary-subtle); border: 1px solid var(--color-primary-border); border-radius: var(--border-radius-md); padding: 0.85rem 1.25rem; margin-bottom: 1.25rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <div class="file-icon-box document" style="background: var(--color-primary); color: #fff;">
                <?= \App\Helpers\Icon::render('folder', '', 18) ?>
            </div>
            <div>
                <div style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; color: var(--color-primary);">Active Folder View</div>
                <div style="font-weight: 700; font-size: 1.1rem; color: var(--text-main);"><?= htmlspecialchars($activeCollection['name']) ?></div>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 0.65rem;">
            <span class="badge-vault badge-admin"><?= count($assets) ?> files (<?= round(($activeCollection['total_bytes'] ?? 0) / (1024 * 1024), 2) ?> MB)</span>
            <button type="button" class="btn-vault btn-primary btn-sm" onclick="Vault.openModal('upload-modal')">
                <?= \App\Helpers\Icon::render('upload', '', 14) ?>
                <span>Upload to this folder</span>
            </button>
            <a href="/assets" class="btn-vault btn-secondary btn-sm">Clear Filter</a>
        </div>
    </div>
<?php endif; ?>

<!-- Filters Bar -->
<div class="card-surface" style="padding: 1rem 1.25rem; margin-bottom: 1.25rem;">
    <form action="/assets" method="GET" style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 220px; position: relative;">
            <input type="text" name="search" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Search by filename..." style="width: 100%; padding: 0.45rem 0.75rem 0.45rem 2rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.85rem;" />
            <span style="position: absolute; left: 0.65rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);">
                <?= \App\Helpers\Icon::render('search', '', 14) ?>
            </span>
        </div>

        <select name="collection" style="padding: 0.45rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.85rem; background: var(--bg-surface); color: var(--text-main);">
            <option value="">All Collections</option>
            <?php foreach ($collections as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ($selectedCollection == $c['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="status" style="padding: 0.45rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.85rem; background: var(--bg-surface); color: var(--text-main);">
            <option value="">All Statuses</option>
            <option value="ready" <?= ($selectedStatus === 'ready') ? 'selected' : '' ?>>Ready</option>
            <option value="quarantined" <?= ($selectedStatus === 'quarantined') ? 'selected' : '' ?>>Quarantined</option>
            <option value="processing" <?= ($selectedStatus === 'processing') ? 'selected' : '' ?>>Processing</option>
        </select>

        <button type="submit" class="btn-vault btn-secondary">Filter</button>
        <?php if (!empty($search) || !empty($selectedCollection) || !empty($selectedStatus)): ?>
            <a href="/assets" class="btn-vault btn-secondary">Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- Assets Table -->
<div class="table-container">
    <table class="table-vault">
        <thead>
            <tr>
                <th>Asset Details</th>
                <th>Collection</th>
                <th>MIME Type</th>
                <th>File Size</th>
                <th>Upload Date</th>
                <th>Status</th>
                <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($assets)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                        No assets found matching the specified criteria.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($assets as $a): ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <div class="file-icon-box <?= in_array($a['file_extension'], ['jpg','jpeg','png','webp','gif']) ? 'image' : (in_array($a['file_extension'], ['pdf','doc','docx']) ? 'document' : 'spreadsheet') ?>">
                                    <?= strtoupper($a['file_extension']) ?>
                                </div>
                                <div>
                                    <div style="font-weight: 600; font-size: 0.875rem; color: var(--text-main);">
                                        <?= htmlspecialchars($a['original_filename']) ?>
                                    </div>
                                    <div style="font-size: 0.725rem; color: var(--text-muted); font-family: monospace;">
                                        SHA-256: <?= substr($a['sha256_checksum'], 0, 16) ?>...
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span style="font-size: 0.825rem; color: var(--text-muted);">
                                <?= htmlspecialchars($a['collection_name'] ?? 'Unorganized') ?>
                            </span>
                        </td>
                        <td>
                            <span style="font-size: 0.8rem; font-family: monospace; color: var(--text-muted);">
                                <?= htmlspecialchars($a['mime_type']) ?>
                            </span>
                        </td>
                        <td>
                            <span style="font-weight: 500; font-size: 0.825rem;">
                                <?= round($a['file_size_bytes'] / 1024, 1) ?> KB
                            </span>
                        </td>
                        <td>
                            <span style="font-size: 0.8rem; color: var(--text-muted);">
                                <?= date('M j, Y H:i', strtotime($a['created_at'])) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge-vault badge-<?= $a['status'] ?>">
                                <?= ucfirst($a['status']) ?>
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <div style="display: inline-flex; gap: 0.35rem;">
                                <?php if ($a['status'] === 'ready'): ?>
                                    <a href="/assets/<?= $a['id'] ?>/download" class="btn-vault btn-secondary btn-sm" title="Download">
                                        <?= \App\Helpers\Icon::render('download', '', 14) ?>
                                    </a>
                                <?php endif; ?>
                                <form action="/assets/<?= $a['id'] ?>/delete" method="POST" onsubmit="return confirm('Delete this asset? This cannot be undone.');" style="display: inline;">
                                    <input type="hidden" name="_csrf_token" value="<?= \App\Session::getCsrfToken() ?>">
                                    <button type="submit" class="btn-vault btn-secondary btn-sm" title="Delete Asset" style="color: var(--color-danger);">
                                        <?= \App\Helpers\Icon::render('trash', '', 14) ?>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Upload Modal -->
<div id="upload-modal" class="modal-backdrop-vault">
    <div class="modal-box-vault">
        <div class="modal-header-vault">
            <div style="font-weight: 700; font-size: 1.05rem;">Upload Digital Assets</div>
            <button type="button" style="background: none; border: none; cursor: pointer; color: #94a3b8;" data-close-modal>
                <?= \App\Helpers\Icon::render('x', '', 18) ?>
            </button>
        </div>
        <div class="modal-body-vault">
            <div style="margin-bottom: 1.25rem;">
                <label for="modal-coll-select" style="font-size: 0.825rem; font-weight: 600; display: block; margin-bottom: 0.35rem; color: var(--text-main);">Assign to Collection / Folder:</label>
                <select id="modal-coll-select" style="width: 100%; padding: 0.55rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.875rem; background: var(--bg-surface); color: var(--text-main);">
                    <option value="">(Unorganized Root)</option>
                    <?php foreach ($collections as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($selectedCollection == $c['id']) ? 'selected' : '' ?>>
                            📁 <?= htmlspecialchars($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">Select which folder will receive and index these files.</div>
            </div>

            <input type="file" id="modal-file-input" multiple style="display: none;" />
            <div id="modal-dropzone" class="dropzone-area">
                <div class="dropzone-icon">
                    <?= \App\Helpers\Icon::render('upload-cloud', '', 24) ?>
                </div>
                <div style="font-weight: 600; font-size: 0.95rem; margin-bottom: 0.25rem;">Select or Drop Files to Upload</div>
                <div style="font-size: 0.8rem; color: var(--text-muted);">Max single file size: 100MB</div>
            </div>
            <div id="modal-upload-list" style="margin-top: 1rem;"></div>
        </div>
        <div class="modal-footer-vault">
            <button type="button" class="btn-vault btn-secondary" data-close-modal>Close</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    VaultUploader.init('modal-dropzone', 'modal-file-input', 'modal-upload-list', 'modal-coll-select');

    // Auto-open modal if URL specifies ?upload=1
    const params = new URLSearchParams(window.location.search);
    if (params.get('upload') === '1') {
        Vault.openModal('upload-modal');
    }
});
</script>
