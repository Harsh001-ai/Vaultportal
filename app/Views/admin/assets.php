<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <div>
        <h1 style="font-size: 1.4rem; font-weight: 700; margin: 0; color: var(--text-main);">All Assets & Selection Manager</h1>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0.25rem 0 0;">Browse, filter, select, and package multi-user assets for secure download delivery.</p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <button type="button" class="btn-vault btn-primary" id="btn-open-export-wizard">
            <?= \App\Helpers\Icon::render('package') ?>
            <span>Package Selected (<span id="selected-count-badge">0</span>)</span>
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
            <a href="/admin/assets" class="btn-vault btn-secondary btn-sm">Clear Filter</a>
        </div>
    </div>
<?php endif; ?>

<!-- Filters & Search Toolbar -->
<div class="card-surface" style="padding: 1rem 1.25rem; margin-bottom: 1.25rem;">
    <form action="/admin/assets" method="GET" style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
        <!-- Search query -->
        <div style="flex: 1; min-width: 220px; position: relative;">
            <input type="text" name="search" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Search by filename or uploader..." style="width: 100%; padding: 0.45rem 0.75rem 0.45rem 2rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.85rem;" />
            <span style="position: absolute; left: 0.65rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);">
                <?= \App\Helpers\Icon::render('search', '', 14) ?>
            </span>
        </div>

        <!-- Uploader filter -->
        <select name="uploader" style="padding: 0.45rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.85rem; background: var(--bg-surface); color: var(--text-main);">
            <option value="">All Uploaders</option>
            <?php foreach ($users as $u): ?>
                <option value="<?= $u['id'] ?>" <?= ($selectedUploader == $u['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['email']) ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <!-- Collection filter -->
        <select name="collection" style="padding: 0.45rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.85rem; background: var(--bg-surface); color: var(--text-main);">
            <option value="">All Collections</option>
            <?php foreach ($collections as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ($selectedCollection == $c['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- Extension filter -->
        <select name="extension" style="padding: 0.45rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.85rem; background: var(--bg-surface); color: var(--text-main);">
            <option value="">All File Types</option>
            <option value="png" <?= ($selectedExtension === 'png') ? 'selected' : '' ?>>PNG</option>
            <option value="jpg" <?= ($selectedExtension === 'jpg') ? 'selected' : '' ?>>JPG</option>
            <option value="pdf" <?= ($selectedExtension === 'pdf') ? 'selected' : '' ?>>PDF</option>
            <option value="csv" <?= ($selectedExtension === 'csv') ? 'selected' : '' ?>>CSV</option>
            <option value="zip" <?= ($selectedExtension === 'zip') ? 'selected' : '' ?>>ZIP</option>
            <option value="docx" <?= ($selectedExtension === 'docx') ? 'selected' : '' ?>>DOCX</option>
        </select>

        <!-- Status filter -->
        <select name="status" style="padding: 0.45rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.85rem; background: var(--bg-surface); color: var(--text-main);">
            <option value="">All Statuses</option>
            <option value="ready" <?= ($selectedStatus === 'ready') ? 'selected' : '' ?>>Ready</option>
            <option value="quarantined" <?= ($selectedStatus === 'quarantined') ? 'selected' : '' ?>>Quarantined</option>
        </select>

        <button type="submit" class="btn-vault btn-secondary">Filter</button>
        <?php if (!empty($search) || !empty($selectedUploader) || !empty($selectedCollection) || !empty($selectedExtension) || !empty($selectedStatus)): ?>
            <a href="/admin/assets" class="btn-vault btn-secondary">Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- Assets Table -->
<div class="table-container">
    <table class="table-vault">
        <thead>
            <tr>
                <th style="width: 40px; text-align: center;">
                    <input type="checkbox" id="select-all-assets" title="Select all on this page" style="cursor: pointer;" />
                </th>
                <th>Asset Filename</th>
                <th>Source Uploader</th>
                <th>Collection</th>
                <th>Size</th>
                <th>Upload Date</th>
                <th>Security Status</th>
                <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($assets)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                        No assets found matching the selected filters.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($assets as $a): ?>
                    <tr>
                        <td style="text-align: center;">
                            <input type="checkbox" class="asset-select-cb" value="<?= $a['id'] ?>" style="cursor: pointer;" />
                        </td>
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
                            <div>
                                <div style="font-weight: 600; font-size: 0.825rem; color: var(--text-main);"><?= htmlspecialchars($a['uploader_name']) ?></div>
                                <div style="font-size: 0.725rem; color: var(--text-muted);"><?= htmlspecialchars($a['uploader_email']) ?></div>
                            </div>
                        </td>
                        <td>
                            <span style="font-size: 0.825rem; color: var(--text-muted);">
                                <?= htmlspecialchars($a['collection_name'] ?? 'Unorganized') ?>
                            </span>
                        </td>
                        <td>
                            <span style="font-size: 0.825rem; font-weight: 500;">
                                <?= round($a['file_size_bytes'] / 1024, 1) ?> KB
                            </span>
                        </td>
                        <td>
                            <span style="font-size: 0.8rem; color: var(--text-muted);">
                                <?= date('M j, Y H:i', strtotime($a['created_at'])) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge-vault badge-<?= $a['status'] ?>" title="<?= htmlspecialchars($a['quarantine_reason'] ?? '') ?>">
                                <?= ucfirst($a['status']) ?>
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <div style="display: inline-flex; gap: 0.35rem;">
                                <a href="/assets/<?= $a['id'] ?>/download" class="btn-vault btn-secondary btn-sm" title="Download Asset">
                                    <?= \App\Helpers\Icon::render('download', '', 14) ?>
                                </a>
                                <form action="/assets/<?= $a['id'] ?>/delete" method="POST" onsubmit="return confirm('Delete this asset? Storage will be reclaimed.');" style="display: inline;">
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

<!-- Sticky Bottom Selection Action Bar -->
<div id="export-selection-bar" style="display: none; position: fixed; bottom: 1.5rem; left: 50%; transform: translateX(-50%); background: #0f172a; color: #fff; padding: 0.85rem 1.5rem; border-radius: var(--border-radius-lg); box-shadow: var(--shadow-xl); z-index: 35; align-items: center; gap: 1.5rem;">
    <div>
        <span style="font-weight: 700; color: #a5b4fc;"><span id="bar-count-badge">0</span> assets selected</span>
        <span style="font-size: 0.775rem; color: #94a3b8; margin-left: 0.5rem;">Ready for export packaging</span>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <button type="button" class="btn-vault btn-primary" onclick="document.getElementById('btn-open-export-wizard').click()">
            <?= \App\Helpers\Icon::render('package', '', 16) ?>
            <span>Launch Export Center</span>
        </button>
        <button type="button" class="btn-vault btn-secondary btn-sm" onclick="document.getElementById('select-all-assets').click()" style="background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.2); color: #fff;">
            Deselect All
        </button>
    </div>
</div>

<script>
// Sync bottom selection bar count badge
const originalUpdateBar = VaultExport.updateSelectionBar.bind(VaultExport);
VaultExport.updateSelectionBar = function() {
    originalUpdateBar();
    const count = this.selectedIds.size;
    const bCount = document.getElementById('bar-count-badge');
    if (bCount) bCount.textContent = count;
};
</script>
