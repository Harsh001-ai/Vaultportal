// public/assets/js/export.js
// Intelligent Download and Export Center Client Controller

window.VaultExport = {
    selectedIds: new Set(),
    reviewData: null,

    init() {
        this.bindCheckboxes();
        this.bindSelectAll();
        this.bindExportTrigger();
    },

    bindCheckboxes() {
        document.querySelectorAll('.asset-select-cb').forEach(cb => {
            cb.addEventListener('change', (e) => {
                const id = parseInt(e.target.value);
                if (e.target.checked) {
                    this.selectedIds.add(id);
                } else {
                    this.selectedIds.delete(id);
                }
                this.updateSelectionBar();
            });
        });
    },

    bindSelectAll() {
        const selectAll = document.getElementById('select-all-assets');
        if (!selectAll) return;

        selectAll.addEventListener('change', (e) => {
            const checked = e.target.checked;
            document.querySelectorAll('.asset-select-cb').forEach(cb => {
                cb.checked = checked;
                const id = parseInt(cb.value);
                if (checked) {
                    this.selectedIds.add(id);
                } else {
                    this.selectedIds.delete(id);
                }
            });
            this.updateSelectionBar();
        });
    },

    updateSelectionBar() {
        const bar = document.getElementById('export-selection-bar');
        const countEl = document.getElementById('selected-count-badge');
        const count = this.selectedIds.size;

        if (countEl) countEl.textContent = count;

        if (bar) {
            if (count > 0) {
                bar.style.display = 'flex';
            } else {
                bar.style.display = 'none';
            }
        }
    },

    bindExportTrigger() {
        const btn = document.getElementById('btn-open-export-wizard');
        if (!btn) return;

        btn.addEventListener('click', () => {
            if (this.selectedIds.size === 0) {
                Vault.toast('Please select at least one asset to export.', 'warning');
                return;
            }
            this.loadReviewStep();
        });
    },

    async loadReviewStep() {
        Vault.openModal('export-wizard-modal');
        const container = document.getElementById('export-wizard-content');
        container.innerHTML = `
            <div style="text-align: center; padding: 2rem;">
                <div style="font-weight: 600; color: var(--color-primary); margin-bottom: 0.5rem;">Analyzing Asset Selection...</div>
                <div style="font-size: 0.85rem; color: var(--text-muted);">Verifying source users, file sizes, and integrity...</div>
            </div>
        `;

        try {
            const res = await Vault.api('/api/admin/exports/review', {
                method: 'POST',
                body: { asset_ids: Array.from(this.selectedIds) }
            });

            this.reviewData = res.data;
            this.renderReviewView();
        } catch (err) {
            container.innerHTML = `
                <div style="padding: 1.5rem; text-align: center; color: var(--color-danger);">
                    <div style="font-weight: 700; margin-bottom: 0.5rem;">Failed to Review Selection</div>
                    <div>${err.message}</div>
                </div>
            `;
        }
    },

    renderReviewView() {
        const data = this.reviewData;
        const container = document.getElementById('export-wizard-content');

        // Render type breakdown badges
        let typeBadges = '';
        for (const [ext, count] of Object.entries(data.type_breakdown)) {
            typeBadges += `<span class="badge-vault badge-user" style="margin-right: 0.35rem; font-size: 0.8rem;">.${ext.toUpperCase()}: ${count}</span>`;
        }

        // Render source users list
        let userListHtml = '';
        data.source_users.forEach(u => {
            userListHtml += `
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.4rem 0; border-bottom: 1px solid var(--border-light); font-size: 0.85rem;">
                    <div>
                        <span style="font-weight: 600;">${u.name}</span>
                        <span style="color: var(--text-muted); font-size: 0.75rem;">(${u.email})</span>
                    </div>
                    <span class="badge-vault badge-admin">${u.count} files</span>
                </div>
            `;
        });

        // Warnings for ineligible or quarantined files
        let warningHtml = '';
        if (data.ineligible_assets.length > 0) {
            warningHtml = `
                <div style="background: var(--color-warning-subtle); border: 1px solid var(--color-warning-border); padding: 0.75rem 1rem; border-radius: var(--border-radius-sm); margin-bottom: 1rem; font-size: 0.825rem;">
                    <div style="font-weight: 700; color: #92400e; margin-bottom: 0.25rem;">Excluded Ineligible Assets (${data.ineligible_assets.length})</div>
                    <ul style="margin: 0; padding-left: 1.25rem; color: #78350f;">
                        ${data.ineligible_assets.map(a => `<li><strong>${a.filename}</strong>: ${a.reason}</li>`).join('')}
                    </ul>
                </div>
            `;
        }

        // Filename conflicts warning
        let conflictHtml = '';
        if (data.filename_conflicts.length > 0) {
            conflictHtml = `
                <div style="background: #f8fafc; border: 1px solid var(--border-light); padding: 0.75rem 1rem; border-radius: var(--border-radius-sm); margin-bottom: 1rem; font-size: 0.825rem;">
                    <div style="font-weight: 600; color: var(--text-main);">Notice: Filename Overlaps Detected</div>
                    <div style="color: var(--text-muted); font-size: 0.75rem;">Archive packaging will automatically resolve collisions into safe numbered copies (e.g., file (1).ext) while preserving uploader attribution.</div>
                </div>
            `;
        }

        container.innerHTML = `
            ${warningHtml}
            ${conflictHtml}

            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.25rem;">
                <div class="card-surface" style="padding: 0.85rem; text-align: center;">
                    <div style="font-size: 0.75rem; color: var(--text-muted);">Eligible Files</div>
                    <div style="font-size: 1.4rem; font-weight: 700; color: var(--color-primary);">${data.total_files}</div>
                </div>
                <div class="card-surface" style="padding: 0.85rem; text-align: center;">
                    <div style="font-size: 0.75rem; color: var(--text-muted);">Uncompressed Size</div>
                    <div style="font-size: 1.4rem; font-weight: 700;">${Vault.formatBytes(data.total_uncompressed_bytes)}</div>
                </div>
                <div class="card-surface" style="padding: 0.85rem; text-align: center;">
                    <div style="font-size: 0.75rem; color: var(--text-muted);">Source Users</div>
                    <div style="font-size: 1.4rem; font-weight: 700; color: var(--color-success);">${data.source_users_count}</div>
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 0.4rem;">File Type Breakdown</label>
                <div>${typeBadges || 'None'}</div>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 0.4rem;">Source Users Attribution</label>
                <div style="max-height: 120px; overflow-y: auto; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); padding: 0.5rem 0.75rem;">
                    ${userListHtml}
                </div>
            </div>

            <hr style="border: 0; border-top: 1px solid var(--border-light); margin: 1.25rem 0;" />

            <div style="margin-bottom: 1rem;">
                <label style="font-weight: 700; font-size: 0.9rem; display: block; margin-bottom: 0.5rem;">Select Export Format</label>
                <div style="display: grid; grid-template-columns: 1fr; gap: 0.5rem;">
                    <label style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.65rem 0.85rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); cursor: pointer;">
                        <input type="radio" name="export_format" value="zip_by_user" checked style="margin-top: 0.2rem;" />
                        <div>
                            <div style="font-weight: 600; font-size: 0.85rem;">ZIP Organized by Source User (Recommended)</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">Folders structured as <code>user_ID_username/type/filename</code> preserving individual ownership.</div>
                        </div>
                    </label>

                    <label style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.65rem 0.85rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); cursor: pointer;">
                        <input type="radio" name="export_format" value="combined_zip" style="margin-top: 0.2rem;" />
                        <div>
                            <div style="font-weight: 600; font-size: 0.85rem;">Combined ZIP (Files + JSON & CSV Manifests)</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">Includes all assets plus comprehensive metadata manifests at the archive root.</div>
                        </div>
                    </label>

                    <label style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.65rem 0.85rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); cursor: pointer;">
                        <input type="radio" name="export_format" value="zip_by_type" style="margin-top: 0.2rem;" />
                        <div>
                            <div style="font-weight: 600; font-size: 0.85rem;">ZIP Organized by File Type</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">Grouped into Images, Documents, Spreadsheets, Presentations, and Other.</div>
                        </div>
                    </label>

                    <label style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.65rem 0.85rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); cursor: pointer;">
                        <input type="radio" name="export_format" value="zip_by_collection" style="margin-top: 0.2rem;" />
                        <div>
                            <div style="font-weight: 600; font-size: 0.85rem;">ZIP Organized by Collection</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">Preserves project and campaign collection folder hierarchy.</div>
                        </div>
                    </label>

                    <label style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.65rem 0.85rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); cursor: pointer;">
                        <input type="radio" name="export_format" value="zip_flat" style="margin-top: 0.2rem;" />
                        <div>
                            <div style="font-weight: 600; font-size: 0.85rem;">Flat ZIP Archive</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">All assets packaged into one root folder with safe collision resolution.</div>
                        </div>
                    </label>

                    <label style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.65rem 0.85rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); cursor: pointer;">
                        <input type="radio" name="export_format" value="manifest_csv" style="margin-top: 0.2rem;" />
                        <div>
                            <div style="font-weight: 600; font-size: 0.85rem;">Audit Manifest Only (CSV)</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">Lightweight spreadsheet manifest containing checksums, sizes, and user details.</div>
                        </div>
                    </label>

                    <label style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.65rem 0.85rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); cursor: pointer;">
                        <input type="radio" name="export_format" value="manifest_json" style="margin-top: 0.2rem;" />
                        <div>
                            <div style="font-weight: 600; font-size: 0.85rem;">Audit Manifest Only (JSON)</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">Structured machine-readable JSON metadata export.</div>
                        </div>
                    </label>
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 0.35rem;">Custom Export Name (Optional)</label>
                <input type="text" id="export-custom-name" placeholder="e.g. Q4_Legal_Delivery" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.85rem;" />
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 0.35rem;">Audit Note / Delivery Purpose</label>
                <input type="text" id="export-notes" placeholder="e.g. Requested for legal audit compliance review" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--border-light); border-radius: var(--border-radius-sm); font-size: 0.85rem;" />
            </div>
        `;

        // Update modal footer
        const footer = document.getElementById('export-wizard-footer');
        footer.innerHTML = `
            <button type="button" class="btn-vault btn-secondary" data-close-modal>Cancel</button>
            <button type="button" class="btn-vault btn-primary" id="btn-start-export-packaging">
                Start Secure Packaging & Download
            </button>
        `;

        // Bind cancel buttons inside modal
        footer.querySelector('[data-close-modal]').addEventListener('click', () => {
            Vault.closeModal('export-wizard-modal');
        });

        // Bind packaging button
        document.getElementById('btn-start-export-packaging').addEventListener('click', () => {
            this.executePackaging();
        });
    },

    async executePackaging() {
        const selectedFormat = document.querySelector('input[name="export_format"]:checked')?.value || 'zip_by_user';
        const customName = document.getElementById('export-custom-name')?.value;
        const notes = document.getElementById('export-notes')?.value;

        const container = document.getElementById('export-wizard-content');
        container.innerHTML = `
            <div style="text-align: center; padding: 3rem 1rem;">
                <div style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--color-primary);">Packaging Assets Securely...</div>
                <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.5rem;">Validating path security, resolving duplicate filenames, and computing checksums...</div>
                <div style="background: #e2e8f0; height: 8px; border-radius: 9999px; overflow: hidden; max-width: 300px; margin: 0 auto;">
                    <div style="width: 60%; height: 100%; background: var(--color-primary); animation: pulse 1s infinite alternate;"></div>
                </div>
            </div>
        `;

        const footer = document.getElementById('export-wizard-footer');
        footer.innerHTML = `<button type="button" class="btn-vault btn-secondary" disabled>Please wait...</button>`;

        try {
            const res = await Vault.api('/api/admin/exports/create', {
                method: 'POST',
                body: {
                    asset_ids: Array.from(this.selectedIds),
                    format: selectedFormat,
                    export_name: customName,
                    notes: notes
                }
            });

            const job = res.data;

            container.innerHTML = `
                <div style="text-align: center; padding: 2rem 1rem;">
                    <div style="width: 56px; height: 56px; border-radius: 50%; background: var(--color-success-subtle); color: var(--color-success); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <div style="font-size: 1.25rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.5rem;">Export Ready for Download!</div>
                    <div style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1.5rem;">
                        Package <strong>${job.export_name}</strong> (${job.total_files} files, ${Vault.formatBytes(job.archive_size_bytes)}) has been packaged. An immutable audit record has been logged.
                    </div>
                    <a href="/download/export/${job.download_token}" class="btn-vault btn-primary" style="font-size: 1rem; padding: 0.75rem 1.5rem; text-decoration: none;">
                        Download Package Now
                    </a>
                </div>
            `;

            footer.innerHTML = `
                <a href="/admin/audit-log" class="btn-vault btn-secondary" style="text-decoration: none;">View Audit Log</a>
                <button type="button" class="btn-vault btn-secondary" onclick="window.location.reload()">Done</button>
            `;

            Vault.toast('Export completed successfully!', 'success');
        } catch (err) {
            container.innerHTML = `
                <div style="text-align: center; padding: 2rem 1rem; color: var(--color-danger);">
                    <div style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem;">Packaging Failed</div>
                    <div style="font-size: 0.85rem;">${err.message}</div>
                </div>
            `;
            footer.innerHTML = `<button type="button" class="btn-vault btn-secondary" onclick="Vault.closeModal('export-wizard-modal')">Close</button>`;
            Vault.toast(err.message, 'danger');
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    VaultExport.init();
});
