<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <div>
        <h1 style="font-size: 1.4rem; font-weight: 700; margin: 0; color: var(--text-main);">User Management & Quotas</h1>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0.25rem 0 0;">Provision accounts, enforce storage limits, and manage authentication status.</p>
    </div>
    <button type="button" class="btn-vault btn-primary" onclick="Vault.openModal('create-user-modal')">
        <?= \App\Helpers\Icon::render('users') ?>
        <span>Provision New User</span>
    </button>
</div>

<!-- Filters -->
<div class="card-surface" style="padding: 1rem 1.25rem; margin-bottom: 1.25rem;">
    <form action="/admin/users" method="GET" style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 220px; position: relative;">
            <input type="text" name="search" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Search by name or email..." style="width: 100%; padding: 0.45rem 0.75rem 0.45rem 2rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.85rem;" />
            <span style="position: absolute; left: 0.65rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);">
                <?= \App\Helpers\Icon::render('search', '', 14) ?>
            </span>
        </div>

        <select name="role" style="padding: 0.45rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.85rem; background: #fff;">
            <option value="">All Roles</option>
            <option value="user" <?= ($selectedRole === 'user') ? 'selected' : '' ?>>Client User</option>
            <option value="admin" <?= ($selectedRole === 'admin') ? 'selected' : '' ?>>Administrator</option>
        </select>

        <select name="status" style="padding: 0.45rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.85rem; background: #fff;">
            <option value="">All Statuses</option>
            <option value="active" <?= ($selectedStatus === 'active') ? 'selected' : '' ?>>Active</option>
            <option value="suspended" <?= ($selectedStatus === 'suspended') ? 'selected' : '' ?>>Suspended</option>
        </select>

        <button type="submit" class="btn-vault btn-secondary">Filter</button>
        <?php if (!empty($search) || !empty($selectedRole) || !empty($selectedStatus)): ?>
            <a href="/admin/users" class="btn-vault btn-secondary">Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- Users Table -->
<div class="table-container">
    <table class="table-vault">
        <thead>
            <tr>
                <th>User Details</th>
                <th>Role</th>
                <th>Storage Quota</th>
                <th>Uploaded Assets</th>
                <th>Account Status</th>
                <th>Registered</th>
                <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <?php 
                    $used = (int)$u['storage_used_bytes'];
                    $quota = (int)$u['storage_quota_bytes'];
                    $pct = $quota > 0 ? min(100, round(($used / $quota) * 100, 1)) : 0;
                ?>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div class="user-avatar" style="background: var(--color-primary-subtle); color: var(--color-primary); font-weight: 700;">
                                <?= strtoupper(substr($u['name'], 0, 1)) ?>
                            </div>
                            <div>
                                <div style="font-weight: 600; font-size: 0.875rem; color: var(--text-main);">
                                    <?= htmlspecialchars($u['name']) ?>
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">
                                    <?= htmlspecialchars($u['email']) ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge-vault <?= $u['role'] === 'admin' ? 'badge-admin' : 'badge-user' ?>">
                            <?= ucfirst($u['role']) ?>
                        </span>
                    </td>
                    <td>
                        <div style="font-size: 0.8rem; font-weight: 500;">
                            <?= round($used / (1024 * 1024), 1) ?> MB / <?= round($quota / (1024 * 1024), 0) ?> MB
                        </div>
                        <div class="quota-bar-wrapper" style="width: 140px;">
                            <div class="quota-bar-fill <?= $pct > 90 ? 'danger' : ($pct > 75 ? 'warning' : '') ?>" style="width: <?= $pct ?>%;"></div>
                        </div>
                    </td>
                    <td>
                        <a href="/admin/assets?uploader=<?= $u['id'] ?>" style="font-weight: 600; font-size: 0.85rem;">
                            <?= $u['asset_count'] ?> assets
                        </a>
                    </td>
                    <td>
                        <span class="badge-vault badge-<?= $u['status'] === 'active' ? 'ready' : 'failed' ?>">
                            <?= ucfirst($u['status']) ?>
                        </span>
                    </td>
                    <td>
                        <span style="font-size: 0.8rem; color: var(--text-muted);">
                            <?= date('M j, Y', strtotime($u['created_at'])) ?>
                        </span>
                    </td>
                    <td style="text-align: right;">
                        <div style="display: inline-flex; gap: 0.35rem;">
                            <!-- Edit Quota Button -->
                            <button type="button" class="btn-vault btn-secondary btn-sm" onclick="editUserQuota(<?= $u['id'] ?>, '<?= htmlspecialchars($u['name'], ENT_QUOTES) ?>', <?= round($quota / (1024 * 1024)) ?>)" title="Change Storage Quota">
                                <?= \App\Helpers\Icon::render('database', '', 14) ?>
                            </button>

                            <!-- Suspend / Reactivate -->
                            <?php if ($u['id'] !== $user['id']): ?>
                                <form action="/admin/users/<?= $u['id'] ?>/toggle-status" method="POST" style="display: inline;" onsubmit="return confirm('Change status for <?= htmlspecialchars($u['name'], ENT_QUOTES) ?>?');">
                                    <input type="hidden" name="_csrf_token" value="<?= \App\Session::getCsrfToken() ?>">
                                    <button type="submit" class="btn-vault btn-secondary btn-sm" style="color: <?= $u['status'] === 'active' ? 'var(--color-danger)' : 'var(--color-success)' ?>;" title="<?= $u['status'] === 'active' ? 'Suspend User' : 'Reactivate User' ?>">
                                        <?= \App\Helpers\Icon::render($u['status'] === 'active' ? 'shield-alert' : 'check-circle', '', 14) ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Create User Modal -->
<div id="create-user-modal" class="modal-backdrop-vault">
    <div class="modal-box-vault" style="max-width: 480px;">
        <form action="/admin/users" method="POST">
            <input type="hidden" name="_csrf_token" value="<?= \App\Session::getCsrfToken() ?>">
            <div class="modal-header-vault">
                <div style="font-weight: 700; font-size: 1.05rem;">Provision New User Account</div>
                <button type="button" style="background: none; border: none; cursor: pointer; color: #94a3b8;" data-close-modal>
                    <?= \App\Helpers\Icon::render('x', '', 18) ?>
                </button>
            </div>
            <div class="modal-body-vault">
                <div style="margin-bottom: 1rem;">
                    <label for="uname" style="font-size: 0.825rem; font-weight: 600; display: block; margin-bottom: 0.35rem;">Full Name</label>
                    <input type="text" id="uname" name="name" required placeholder="e.g. David Miller" style="width: 100%; padding: 0.55rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.9rem;" />
                </div>
                <div style="margin-bottom: 1rem;">
                    <label for="uemail" style="font-size: 0.825rem; font-weight: 600; display: block; margin-bottom: 0.35rem;">Email Address</label>
                    <input type="email" id="uemail" name="email" required placeholder="name@company.com" style="width: 100%; padding: 0.55rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.9rem;" />
                </div>
                <div style="margin-bottom: 1rem;">
                    <label for="upass" style="font-size: 0.825rem; font-weight: 600; display: block; margin-bottom: 0.35rem;">Initial Password</label>
                    <input type="password" id="upass" name="password" required minlength="8" placeholder="At least 8 characters" style="width: 100%; padding: 0.55rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.9rem;" />
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label for="urole" style="font-size: 0.825rem; font-weight: 600; display: block; margin-bottom: 0.35rem;">Role</label>
                        <select id="urole" name="role" style="width: 100%; padding: 0.55rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.85rem; background: #fff;">
                            <option value="user">Client User</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    <div>
                        <label for="uquota" style="font-size: 0.825rem; font-weight: 600; display: block; margin-bottom: 0.35rem;">Storage Quota (MB)</label>
                        <input type="number" id="uquota" name="quota_mb" value="1024" min="50" style="width: 100%; padding: 0.55rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.85rem;" />
                    </div>
                </div>
            </div>
            <div class="modal-footer-vault">
                <button type="button" class="btn-vault btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn-vault btn-primary">Provision Account</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Quota Modal -->
<div id="edit-quota-modal" class="modal-backdrop-vault">
    <div class="modal-box-vault" style="max-width: 420px;">
        <form id="edit-quota-form" method="POST">
            <input type="hidden" name="_csrf_token" value="<?= \App\Session::getCsrfToken() ?>">
            <div class="modal-header-vault">
                <div style="font-weight: 700; font-size: 1.05rem;">Update Storage Quota</div>
                <button type="button" style="background: none; border: none; cursor: pointer; color: #94a3b8;" data-close-modal>
                    <?= \App\Helpers\Icon::render('x', '', 18) ?>
                </button>
            </div>
            <div class="modal-body-vault">
                <div style="margin-bottom: 1rem; font-size: 0.85rem; color: var(--text-muted);" id="quota-modal-user-label"></div>
                <div style="margin-bottom: 1rem;">
                    <label for="modal-quota-mb" style="font-size: 0.825rem; font-weight: 600; display: block; margin-bottom: 0.35rem;">Storage Quota (MB)</label>
                    <input type="number" id="modal-quota-mb" name="quota_mb" required min="50" style="width: 100%; padding: 0.55rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.9rem;" />
                </div>
            </div>
            <div class="modal-footer-vault">
                <button type="button" class="btn-vault btn-secondary" data-close-modal>Cancel</button>
                <button type="submit" class="btn-vault btn-primary">Save Quota</button>
            </div>
        </form>
    </div>
</div>

<script>
function editUserQuota(userId, userName, currentMb) {
    document.getElementById('edit-quota-form').action = '/admin/users/' + userId + '/update-quota';
    document.getElementById('quota-modal-user-label').textContent = 'Setting storage quota for ' + userName;
    document.getElementById('modal-quota-mb').value = currentMb;
    Vault.openModal('edit-quota-modal');
}
</script>
