<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.4rem; font-weight: 700; margin: 0; color: var(--text-main);">Administrator Workspace</h1>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0.25rem 0 0;">Comprehensive oversight, asset management, and export delivery controls.</p>
    </div>
    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
        <a href="/admin/assets" class="btn-vault btn-primary">
            <?= \App\Helpers\Icon::render('files') ?>
            <span>Select & Export Assets</span>
        </a>
        <a href="/admin/audit-log" class="btn-vault btn-secondary">
            <?= \App\Helpers\Icon::render('history') ?>
            <span>Download Audit Log</span>
        </a>
    </div>
</div>

<!-- Admin Platform Metrics Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4" style="margin-bottom: 1.5rem;">
    <!-- Total Registered Users -->
    <div class="card-surface stat-card">
        <div>
            <div class="stat-title">Registered Users</div>
            <div class="stat-value"><?= (int)($userMetrics['total_users'] ?? 0) ?></div>
            <div class="stat-meta">
                <span style="color: var(--color-success); font-weight: 600;"><?= (int)($userMetrics['active_users'] ?? 0) ?> active</span> • 
                <span style="color: var(--color-danger);"><?= (int)($userMetrics['suspended_users'] ?? 0) ?> suspended</span>
            </div>
        </div>
        <div class="stat-icon primary">
            <?= \App\Helpers\Icon::render('users') ?>
        </div>
    </div>

    <!-- Total Uploaded Assets -->
    <div class="card-surface stat-card">
        <div>
            <div class="stat-title">Platform Assets</div>
            <div class="stat-value"><?= (int)($assetMetrics['total_assets'] ?? 0) ?></div>
            <div class="stat-meta">Across all client accounts</div>
        </div>
        <div class="stat-icon secondary">
            <?= \App\Helpers\Icon::render('files') ?>
        </div>
    </div>

    <!-- Storage Consumed -->
    <div class="card-surface stat-card">
        <div>
            <div class="stat-title">Storage Consumed</div>
            <div class="stat-value"><?= round((int)($assetMetrics['total_bytes'] ?? 0) / (1024 * 1024), 2) ?> MB</div>
            <div class="stat-meta">Isolated storage blobs</div>
        </div>
        <div class="stat-icon primary">
            <?= \App\Helpers\Icon::render('database') ?>
        </div>
    </div>

    <!-- Quarantined Assets -->
    <div class="card-surface stat-card">
        <div>
            <div class="stat-title">Quarantined Assets</div>
            <div class="stat-value" style="color: <?= ((int)($assetMetrics['quarantined_assets'] ?? 0) > 0) ? 'var(--color-danger)' : 'var(--color-success)' ?>;">
                <?= (int)($assetMetrics['quarantined_assets'] ?? 0) ?>
            </div>
            <div class="stat-meta"><?= ((int)($assetMetrics['quarantined_assets'] ?? 0) > 0) ? 'Flagged for suspicious code' : 'Zero threats detected' ?></div>
        </div>
        <div class="stat-icon <?= ((int)($assetMetrics['quarantined_assets'] ?? 0) > 0) ? 'danger' : 'success' ?>">
            <?= \App\Helpers\Icon::render('shield-alert') ?>
        </div>
    </div>

    <!-- Total Downloads Recorded -->
    <div class="card-surface stat-card">
        <div>
            <div class="stat-title">Downloads Recorded</div>
            <div class="stat-value"><?= (int)($downloadMetrics['total_downloads'] ?? 0) ?></div>
            <div class="stat-meta">Audited delivery events</div>
        </div>
        <div class="stat-icon success">
            <?= \App\Helpers\Icon::render('download') ?>
        </div>
    </div>
</div>

<!-- Main Content Grid: Responsive 3-Column Split (2 cols left for Uploads & Audits, 1 col right for Exports) -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left Section: Recent Client Uploads & Download Audit Records (spans 2 columns on lg) -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Recent Assets Table -->
        <div class="card-surface" style="padding: 1.25rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <div>
                    <h2 style="font-size: 1rem; font-weight: 700; margin: 0;">Recent Client Uploads</h2>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">Real-time stream with source user attribution</div>
                </div>
                <a href="/admin/assets" style="font-size: 0.8rem; font-weight: 600;">Manage All →</a>
            </div>

            <div class="w-full overflow-x-auto table-container" style="border: none;">
                <table class="table-vault">
                    <thead>
                        <tr>
                            <th>Asset</th>
                            <th>Source User</th>
                            <th>Size</th>
                            <th>Status</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentAssets)): ?>
                            <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">No uploads found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentAssets as $a): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.65rem;">
                                            <div class="file-icon-box <?= in_array($a['file_extension'], ['jpg','jpeg','png','webp','gif']) ? 'image' : (in_array($a['file_extension'], ['pdf','doc','docx']) ? 'document' : 'spreadsheet') ?>">
                                                <?= strtoupper($a['file_extension']) ?>
                                            </div>
                                            <div>
                                                <div style="font-weight: 600; font-size: 0.825rem; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                    <?= htmlspecialchars($a['original_filename']) ?>
                                                </div>
                                                <div style="font-size: 0.7rem; color: var(--text-muted);">
                                                    <?= date('M j, H:i', strtotime($a['created_at'])) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <div style="font-weight: 600; font-size: 0.8rem;"><?= htmlspecialchars($a['uploader_name']) ?></div>
                                            <div style="font-size: 0.7rem; color: var(--text-muted);"><?= htmlspecialchars($a['uploader_email']) ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <span style="font-size: 0.8rem;"><?= round($a['file_size_bytes'] / 1024, 1) ?> KB</span>
                                    </td>
                                    <td>
                                        <span class="badge-vault badge-<?= $a['status'] ?>">
                                            <?= ucfirst($a['status']) ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <a href="/assets/<?= $a['id'] ?>/download" class="btn-vault btn-secondary btn-sm" title="Download Asset">
                                            <?= \App\Helpers\Icon::render('download', '', 14) ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Download Audits Feed -->
        <div class="card-surface" style="padding: 1.25rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <div>
                    <h2 style="font-size: 1rem; font-weight: 700; margin: 0;">Download Audit Records</h2>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">Immutable security records of all asset and archive egress events</div>
                </div>
                <a href="/admin/audit-log" class="btn-vault btn-secondary btn-sm">Full Audit Trail →</a>
            </div>

            <div class="w-full overflow-x-auto table-container" style="border: none;">
                <table class="table-vault">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Downloaded By</th>
                            <th>Target Package / Asset</th>
                            <th>IP Address</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentDownloads)): ?>
                            <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">No download events recorded yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentDownloads as $dl): ?>
                                <tr>
                                    <td><span style="font-size: 0.8rem; font-family: monospace;"><?= date('Y-m-d H:i:s', strtotime($dl['downloaded_at'])) ?></span></td>
                                    <td><span style="font-weight: 600; font-size: 0.825rem;"><?= htmlspecialchars($dl['downloaded_by_name']) ?></span></td>
                                    <td>
                                        <span style="font-size: 0.825rem;">
                                            <?= htmlspecialchars($dl['export_name'] ?? $dl['direct_filename'] ?? 'Direct Asset Download') ?>
                                        </span>
                                    </td>
                                    <td><span style="font-size: 0.8rem; font-family: monospace; color: var(--text-muted);"><?= htmlspecialchars($dl['ip_address']) ?></span></td>
                                    <td>
                                        <span class="badge-vault badge-<?= $dl['download_status'] === 'success' ? 'ready' : 'failed' ?>">
                                            <?= ucfirst($dl['download_status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Section: Export Packages (spans 1 column on lg) -->
    <div class="lg:col-span-1 space-y-6">
        <div class="card-surface" style="padding: 1.25rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <div>
                    <h2 style="font-size: 1rem; font-weight: 700; margin: 0;">Export Packages</h2>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">Latest packaged deliveries</div>
                </div>
                <a href="/admin/exports" style="font-size: 0.8rem; font-weight: 600;">Export Center →</a>
            </div>

            <?php if (empty($recentExports)): ?>
                <div style="text-align: center; padding: 2rem; color: var(--text-muted); font-size: 0.85rem;">
                    No export packages generated yet. Go to <a href="/admin/assets">All Assets</a> to select files and build your first export.
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <?php foreach ($recentExports as $exp): ?>
                        <div style="border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); padding: 0.75rem; display: flex; justify-content: space-between; align-items: center; gap: 0.5rem;">
                            <div style="min-width: 0; flex: 1;">
                                <div style="font-weight: 600; font-size: 0.85rem; color: var(--text-main); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?= htmlspecialchars($exp['export_name']) ?>
                                </div>
                                <div style="font-size: 0.725rem; color: var(--text-muted); margin-top: 0.2rem;">
                                    Format: <code><?= htmlspecialchars($exp['format']) ?></code> • <?= $exp['total_files'] ?> files • <?= round($exp['archive_size_bytes'] / 1024, 1) ?> KB
                                </div>
                                <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.2rem;">
                                    Packaged by <strong><?= htmlspecialchars($exp['admin_name']) ?></strong> on <?= date('M j, H:i', strtotime($exp['created_at'])) ?>
                                </div>
                            </div>
                            <div style="flex-shrink: 0;">
                                <?php if ($exp['status'] === 'ready'): ?>
                                    <a href="/download/export/<?= $exp['download_token'] ?>" class="btn-vault btn-primary btn-sm" title="Download Archive">
                                        <?= \App\Helpers\Icon::render('download', '', 14) ?>
                                        <span>Download</span>
                                    </a>
                                <?php else: ?>
                                    <span class="badge-vault badge-<?= $exp['status'] ?>"><?= ucfirst($exp['status']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
