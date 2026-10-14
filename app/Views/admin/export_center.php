<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <div>
        <h1 style="font-size: 1.4rem; font-weight: 700; margin: 0; color: var(--text-main);">Export Center</h1>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0.25rem 0 0;">Manage archive packaging jobs, download tokens, and delivery packages.</p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <a href="/admin/assets" class="btn-vault btn-primary">
            <?= \App\Helpers\Icon::render('package') ?>
            <span>New Export Selection</span>
        </a>
        <a href="/admin/audit-log" class="btn-vault btn-secondary">
            <?= \App\Helpers\Icon::render('history') ?>
            <span>View Download Logs</span>
        </a>
    </div>
</div>

<div class="table-container">
    <table class="table-vault">
        <thead>
            <tr>
                <th>Export Name & ID</th>
                <th>Format</th>
                <th>Assets Included</th>
                <th>Source Users</th>
                <th>Archive Size</th>
                <th>Admin Creator</th>
                <th>Expires</th>
                <th>Status</th>
                <th style="text-align: right;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($jobs)): ?>
                <tr>
                    <td colspan="9" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                        No export packages generated yet. Go to <a href="/admin/assets">All Assets</a> to select files and trigger an export.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($jobs as $j): ?>
                    <tr>
                        <td>
                            <div>
                                <div style="font-weight: 600; font-size: 0.875rem; color: var(--text-main);">
                                    <?= htmlspecialchars($j['export_name']) ?>
                                </div>
                                <div style="font-size: 0.725rem; color: var(--text-muted); font-family: monospace;">
                                    ID: <?= substr($j['uuid'], 0, 14) ?>...
                                </div>
                                <?php if (!empty($j['export_notes'])): ?>
                                    <div style="font-size: 0.725rem; color: var(--color-primary); margin-top: 0.2rem;">
                                        Note: <?= htmlspecialchars($j['export_notes']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="badge-vault badge-admin">
                                <?= htmlspecialchars($j['format']) ?>
                            </span>
                        </td>
                        <td>
                            <strong><?= $j['total_files'] ?></strong> files
                        </td>
                        <td>
                            <div style="max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 0.8rem;">
                                <?php 
                                    $names = array_map(fn($u) => $u['name'], $j['source_users']);
                                    echo htmlspecialchars(implode(', ', $names) ?: 'None');
                                ?>
                            </div>
                        </td>
                        <td>
                            <span style="font-size: 0.825rem; font-weight: 500;">
                                <?= $j['archive_size_bytes'] > 0 ? round($j['archive_size_bytes'] / 1024, 1) . ' KB' : 'N/A' ?>
                            </span>
                        </td>
                        <td>
                            <div style="font-size: 0.8rem; font-weight: 500;"><?= htmlspecialchars($j['admin_name']) ?></div>
                            <div style="font-size: 0.7rem; color: var(--text-muted);"><?= date('M j, H:i', strtotime($j['created_at'])) ?></div>
                        </td>
                        <td>
                            <span style="font-size: 0.8rem; color: var(--text-muted);">
                                <?= $j['token_expires_at'] ? date('M j, H:i', strtotime($j['token_expires_at'])) : '—' ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge-vault badge-<?= $j['status'] ?>">
                                <?= ucfirst($j['status']) ?>
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <?php if ($j['status'] === 'ready' && strtotime($j['token_expires_at']) > time()): ?>
                                <a href="/download/export/<?= $j['download_token'] ?>" class="btn-vault btn-primary btn-sm" title="Download Export Package">
                                    <?= \App\Helpers\Icon::render('download', '', 14) ?>
                                    <span>Download</span>
                                </a>
                            <?php else: ?>
                                <span style="font-size: 0.75rem; color: var(--text-muted);">Unavailable</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
