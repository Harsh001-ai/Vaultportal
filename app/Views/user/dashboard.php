<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <div>
        <h1 style="font-size: 1.4rem; font-weight: 700; margin: 0; color: var(--text-main);">Client Workspace</h1>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0.25rem 0 0;">Upload, manage, and monitor your enterprise digital assets.</p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <a href="/assets" class="btn-vault btn-secondary">
            <?= \App\Helpers\Icon::render('files') ?>
            <span>View All My Assets</span>
        </a>
    </div>
</div>

<!-- Stat Cards Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4" style="margin-bottom: 1.5rem;">
    <!-- Total Assets -->
    <div class="card-surface stat-card">
        <div>
            <div class="stat-title">Total Uploaded Files</div>
            <div class="stat-value"><?= $totalAssets ?></div>
            <div class="stat-meta">Across all collections</div>
        </div>
        <div class="stat-icon primary">
            <?= \App\Helpers\Icon::render('files') ?>
        </div>
    </div>

    <!-- Storage Quota -->
    <div class="card-surface stat-card">
        <div style="flex: 1; margin-right: 0.75rem;">
            <div class="stat-title">Storage Quota</div>
            <div class="stat-value"><?= round($used / (1024 * 1024), 1) ?> MB</div>
            <div class="stat-meta">of <?= round($quota / (1024 * 1024), 0) ?> MB (<?= $quotaPercent ?>%)</div>
            <div class="quota-bar-wrapper">
                <div class="quota-bar-fill <?= $quotaPercent > 90 ? 'danger' : ($quotaPercent > 75 ? 'warning' : '') ?>" style="width: <?= $quotaPercent ?>%;"></div>
            </div>
        </div>
        <div class="stat-icon secondary">
            <?= \App\Helpers\Icon::render('database') ?>
        </div>
    </div>

    <!-- Security Status -->
    <div class="card-surface stat-card">
        <div>
            <div class="stat-title">Security Review</div>
            <div class="stat-value" style="color: <?= $quarantinedCount > 0 ? 'var(--color-danger)' : 'var(--color-success)' ?>;">
                <?= $quarantinedCount > 0 ? "{$quarantinedCount} Quarantined" : 'Clean' ?>
            </div>
            <div class="stat-meta"><?= $quarantinedCount > 0 ? 'Action required by admin' : 'All assets verified' ?></div>
        </div>
        <div class="stat-icon <?= $quarantinedCount > 0 ? 'danger' : 'success' ?>">
            <?= \App\Helpers\Icon::render($quarantinedCount > 0 ? 'shield-alert' : 'check-circle') ?>
        </div>
    </div>
</div>

<!-- Drag-and-Drop Quick Upload Section -->
<div class="card-surface" style="padding: 1.5rem; margin-bottom: 1.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <div>
            <h2 style="font-size: 1.05rem; font-weight: 700; margin: 0; color: var(--text-main);">Upload Assets</h2>
            <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0.2rem 0 0;">Drag and drop files here. Supported: Images, PDFs, Documents, Spreadsheets, Archives (Max 100MB).</p>
        </div>
        <?php if (!empty($collections)): ?>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <label for="upload-coll-select" style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted);">Assign to Collection:</label>
                <select id="upload-coll-select" style="padding: 0.4rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.85rem; background: #fff;">
                    <option value="">(Unorganized Root)</option>
                    <?php foreach ($collections as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
    </div>

    <input type="file" id="user-file-input" multiple style="display: none;" />

    <div id="user-dropzone" class="dropzone-area">
        <div class="dropzone-icon">
            <?= \App\Helpers\Icon::render('upload-cloud', '', 24) ?>
        </div>
        <div style="font-weight: 600; font-size: 0.95rem; margin-bottom: 0.25rem;">
            Click to browse or drop your files here
        </div>
        <div style="font-size: 0.8rem; color: var(--text-muted);">
            Files are automatically validated, isolated outside public web root, and hashed via SHA-256.
        </div>
    </div>

    <!-- Active Upload Progress Feed -->
    <div id="upload-progress-list" style="margin-top: 1rem;"></div>
</div>

<!-- Recent Assets & Activity Responsive Grid Layout -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Recent Assets Table -->
    <div class="lg:col-span-2 space-y-6">
        <div class="card-surface" style="padding: 1.25rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h2 style="font-size: 1rem; font-weight: 700; margin: 0;">Recent Uploads</h2>
                <a href="/assets" style="font-size: 0.8rem; font-weight: 600;">View all →</a>
            </div>

            <?php if (empty($recentAssets)): ?>
                <div style="text-align: center; padding: 2rem; color: var(--text-muted); font-size: 0.85rem;">
                    No uploads found. Drag and drop your first file above to get started.
                </div>
            <?php else: ?>
                <div class="w-full overflow-x-auto table-container" style="border: none;">
                    <table class="table-vault">
                        <thead>
                            <tr>
                                <th>File Name</th>
                                <th>Collection</th>
                                <th>Size</th>
                                <th>Status</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentAssets as $a): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <div class="file-icon-box <?= in_array($a['file_extension'], ['jpg','jpeg','png','webp','gif']) ? 'image' : (in_array($a['file_extension'], ['pdf','doc','docx']) ? 'document' : 'spreadsheet') ?>">
                                                <?= strtoupper($a['file_extension']) ?>
                                            </div>
                                            <div>
                                                <div style="font-weight: 600; font-size: 0.85rem; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                    <?= htmlspecialchars($a['original_filename']) ?>
                                                </div>
                                                <div style="font-size: 0.725rem; color: var(--text-muted); font-family: monospace;">
                                                    <?= substr($a['sha256_checksum'], 0, 12) ?>...
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span style="font-size: 0.8rem; color: var(--text-muted);">
                                            <?= htmlspecialchars($a['collection_name'] ?? 'Unorganized') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="font-size: 0.8rem; font-weight: 500;">
                                            <?= round($a['file_size_bytes'] / 1024, 1) ?> KB
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-vault badge-<?= $a['status'] ?>">
                                            <?= ucfirst($a['status']) ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <?php if ($a['status'] === 'ready'): ?>
                                            <a href="/assets/<?= $a['id'] ?>/download" class="btn-vault btn-secondary btn-sm" title="Download Asset">
                                                <?= \App\Helpers\Icon::render('download', '', 14) ?>
                                            </a>
                                        <?php else: ?>
                                            <span title="File is quarantined for review" style="color: var(--color-danger); cursor: help;">
                                                <?= \App\Helpers\Icon::render('shield-alert', '', 16) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="lg:col-span-1 space-y-6">
        <div class="card-surface" style="padding: 1.25rem;">
            <h2 style="font-size: 1rem; font-weight: 700; margin: 0 0 1rem;">Recent Activity</h2>
            <?php if (empty($activities)): ?>
                <div style="text-align: center; padding: 1.5rem; color: var(--text-muted); font-size: 0.85rem;">
                    No recent activity recorded.
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 0.85rem;">
                    <?php foreach ($activities as $act): ?>
                        <div style="display: flex; align-items: flex-start; gap: 0.65rem; font-size: 0.825rem; border-bottom: 1px solid var(--border-light); padding-bottom: 0.65rem;">
                            <div style="color: var(--color-primary); margin-top: 0.15rem;">
                                <?= \App\Helpers\Icon::render('history', '', 16) ?>
                            </div>
                            <div>
                                <div style="font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($act['action']) ?></div>
                                <div style="font-size: 0.725rem; color: var(--text-muted);"><?= date('M j, Y H:i', strtotime($act['created_at'])) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    VaultUploader.init('user-dropzone', 'user-file-input', 'upload-progress-list', 'upload-coll-select');
});
</script>
