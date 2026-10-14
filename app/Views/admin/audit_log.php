<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <div>
        <h1 style="font-size: 1.4rem; font-weight: 700; margin: 0; color: var(--text-main);">Download History & Audit Trail</h1>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0.25rem 0 0;">Append-only compliance log recording every administrative download, source users, and delivery status.</p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <a href="/admin/audit-log/export-csv?<?= http_build_query($filters) ?>" class="btn-vault btn-secondary">
            <?= \App\Helpers\Icon::render('download') ?>
            <span>Export Audit Report (CSV)</span>
        </a>
    </div>
</div>

<!-- Filter Toolbar -->
<div class="card-surface" style="padding: 1rem 1.25rem; margin-bottom: 1.25rem;">
    <form action="/admin/audit-log" method="GET" style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
        <!-- Search query -->
        <div style="flex: 1; min-width: 200px; position: relative;">
            <input type="text" name="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" placeholder="Search package or admin..." style="width: 100%; padding: 0.45rem 0.75rem 0.45rem 2rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.85rem;" />
            <span style="position: absolute; left: 0.65rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);">
                <?= \App\Helpers\Icon::render('search', '', 14) ?>
            </span>
        </div>

        <!-- Admin Filter -->
        <select name="admin_id" style="padding: 0.45rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.85rem; background: #fff;">
            <option value="">All Administrators</option>
            <?php foreach ($admins as $adm): ?>
                <option value="<?= $adm['id'] ?>" <?= ($filters['admin_id'] == $adm['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($adm['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- Source User Filter -->
        <select name="source_user_id" style="padding: 0.45rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.85rem; background: #fff;">
            <option value="">All Source Users</option>
            <?php foreach ($allUsers as $u): ?>
                <option value="<?= $u['id'] ?>" <?= ($filters['source_user_id'] == $u['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($u['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- Format Filter -->
        <select name="format" style="padding: 0.45rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.85rem; background: #fff;">
            <option value="">All Formats</option>
            <?php foreach ($formats as $k => $lbl): ?>
                <option value="<?= $k ?>" <?= ($filters['format'] === $k) ? 'selected' : '' ?>><?= $lbl ?></option>
            <?php endforeach; ?>
        </select>

        <!-- Status Filter -->
        <select name="status" style="padding: 0.45rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.85rem; background: #fff;">
            <option value="">All Statuses</option>
            <option value="success" <?= ($filters['status'] === 'success') ? 'selected' : '' ?>>Success</option>
            <option value="failed" <?= ($filters['status'] === 'failed') ? 'selected' : '' ?>>Failed</option>
        </select>

        <!-- Date Range -->
        <input type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>" style="padding: 0.45rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.85rem; background: #fff;" title="Date From" />
        <input type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>" style="padding: 0.45rem 0.65rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.85rem; background: #fff;" title="Date To" />

        <button type="submit" class="btn-vault btn-secondary">Filter</button>
        <a href="/admin/audit-log" class="btn-vault btn-secondary">Reset</a>
    </form>
</div>

<!-- Audit Log Table -->
<div class="table-container">
    <table class="table-vault">
        <thead>
            <tr>
                <th>Download Timestamp</th>
                <th>Administrator</th>
                <th>Export / Asset Name</th>
                <th>Source Users</th>
                <th>Format</th>
                <th>File Count</th>
                <th>Total Size</th>
                <th>IP & Client</th>
                <th>Delivery Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($records)): ?>
                <tr>
                    <td colspan="9" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                        No download audit logs found matching the filter criteria.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($records as $r): ?>
                    <tr>
                        <td>
                            <span style="font-size: 0.8rem; font-family: monospace;">
                                <?= date('Y-m-d H:i:s', strtotime($r['downloaded_at'])) ?>
                            </span>
                        </td>
                        <td>
                            <div>
                                <div style="font-weight: 600; font-size: 0.85rem; color: var(--text-main);">
                                    <?= htmlspecialchars($r['downloaded_by_name']) ?>
                                </div>
                                <div style="font-size: 0.725rem; color: var(--text-muted);">
                                    <?= htmlspecialchars($r['admin_email'] ?? '') ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div>
                                <div style="font-weight: 600; font-size: 0.85rem;">
                                    <?= htmlspecialchars($r['export_name'] ?? $r['direct_filename'] ?? 'Direct Asset Download') ?>
                                </div>
                                <?php if (!empty($r['export_uuid'])): ?>
                                    <div style="font-size: 0.7rem; color: var(--text-muted); font-family: monospace;">
                                        UUID: <?= substr($r['export_uuid'], 0, 14) ?>...
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php if (!empty($r['source_users'])): ?>
                                <div style="max-width: 170px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 0.8rem;">
                                    <?php 
                                        $uNames = array_map(fn($su) => $su['name'], $r['source_users']);
                                        echo htmlspecialchars(implode(', ', $uNames));
                                    ?>
                                </div>
                            <?php else: ?>
                                <span style="font-size: 0.8rem; color: var(--text-muted);">Direct</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge-vault badge-user" style="font-size: 0.725rem;">
                                <?= htmlspecialchars($r['export_format'] ?? 'Direct File') ?>
                            </span>
                        </td>
                        <td>
                            <strong><?= $r['total_files'] ?? 1 ?></strong>
                        </td>
                        <td>
                            <span style="font-size: 0.825rem; font-weight: 500;">
                                <?= round(($r['archive_size_bytes'] ?? $r['direct_file_size'] ?? 0) / 1024, 1) ?> KB
                            </span>
                        </td>
                        <td>
                            <div style="font-size: 0.75rem; font-family: monospace; color: var(--text-muted);">
                                <?= htmlspecialchars($r['ip_address']) ?>
                            </div>
                        </td>
                        <td>
                            <span class="badge-vault badge-<?= $r['download_status'] === 'success' ? 'ready' : 'failed' ?>">
                                <?= ucfirst($r['download_status']) ?>
                            </span>
                            <?php if (!empty($r['error_message'])): ?>
                                <div style="font-size: 0.7rem; color: var(--color-danger); margin-top: 0.2rem;">
                                    <?= htmlspecialchars($r['error_message']) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.25rem;">
        <div style="font-size: 0.825rem; color: var(--text-muted);">
            Showing page <?= $page ?> of <?= $totalPages ?> (Total: <?= $total ?> records)
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <?php if ($page > 1): ?>
                <a href="/admin/audit-log?<?= http_build_query(array_merge($filters, ['page' => $page - 1])) ?>" class="btn-vault btn-secondary btn-sm">Previous</a>
            <?php endif; ?>
            <?php if ($page < $totalPages): ?>
                <a href="/admin/audit-log?<?= http_build_query(array_merge($filters, ['page' => $page + 1])) ?>" class="btn-vault btn-secondary btn-sm">Next</a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
