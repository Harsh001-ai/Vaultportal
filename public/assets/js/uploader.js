// public/assets/js/uploader.js
// Secure Multi-File Drag-and-Drop Uploader with Progress

window.VaultUploader = {
    init(dropzoneId, inputId, listContainerId, collectionSelectId = null) {
        const dropzone = document.getElementById(dropzoneId);
        const fileInput = document.getElementById(inputId);
        const listContainer = document.getElementById(listContainerId);

        if (!dropzone || !fileInput) return;

        // Click to browse
        dropzone.addEventListener('click', () => fileInput.click());

        // Drag events
        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.add('dragover');
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove('dragover');
            });
        });

        // Drop file handling
        dropzone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files.length) {
                this.handleFiles(files, listContainer, collectionSelectId);
            }
        });

        // Input change
        fileInput.addEventListener('change', (e) => {
            if (fileInput.files.length) {
                this.handleFiles(fileInput.files, listContainer, collectionSelectId);
                fileInput.value = ''; // Reset
            }
        });
    },

    handleFiles(files, listContainer, collectionSelectId) {
        const collSelect = collectionSelectId ? document.getElementById(collectionSelectId) : null;
        const collectionId = collSelect ? collSelect.value : null;

        Array.from(files).forEach(file => {
            this.uploadSingleFile(file, listContainer, collectionId);
        });
    },

    uploadSingleFile(file, listContainer, collectionId) {
        const fileId = 'file_' + Math.random().toString(36).substr(2, 9);
        const itemEl = document.createElement('div');
        itemEl.id = fileId;
        itemEl.className = 'card-surface';
        itemEl.style.padding = '0.85rem 1.25rem';
        itemEl.style.marginBottom = '0.75rem';

        itemEl.innerHTML = `
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div class="file-icon-box document">DOC</div>
                    <div>
                        <div style="font-weight: 600; font-size: 0.85rem;">${file.name}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">${Vault.formatBytes(file.size)}</div>
                    </div>
                </div>
                <div class="status-indicator" style="font-size: 0.75rem; font-weight: 600; color: var(--color-primary);">Uploading (0%)...</div>
            </div>
            <div style="background: #e2e8f0; height: 6px; border-radius: 9999px; overflow: hidden;">
                <div class="progress-bar-fill" style="width: 0%; height: 100%; background: var(--color-primary); transition: width 150ms ease;"></div>
            </div>
        `;

        if (listContainer) {
            listContainer.prepend(itemEl);
        }

        const formData = new FormData();
        formData.append('file', file);
        if (collectionId) {
            formData.append('collection_id', collectionId);
        }
        formData.append('_csrf_token', Vault.csrfToken);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/api/assets/upload', true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('X-CSRF-TOKEN', Vault.csrfToken);

        xhr.upload.onprogress = (e) => {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                const fill = itemEl.querySelector('.progress-bar-fill');
                const status = itemEl.querySelector('.status-indicator');
                if (fill) fill.style.width = percent + '%';
                if (status) status.textContent = `Uploading (${percent}%)...`;
            }
        };

        xhr.onload = () => {
            const fill = itemEl.querySelector('.progress-bar-fill');
            const status = itemEl.querySelector('.status-indicator');

            try {
                const res = JSON.parse(xhr.responseText);
                if (xhr.status === 200 || xhr.status === 201) {
                    if (fill) {
                        fill.style.width = '100%';
                        fill.style.background = 'var(--color-success)';
                    }
                    if (status) {
                        if (res.data?.status === 'quarantined') {
                            status.textContent = 'Quarantined for Review';
                            status.style.color = 'var(--color-danger)';
                            Vault.toast(`Asset '${file.name}' quarantined: suspicious content detected.`, 'warning');
                        } else {
                            status.textContent = 'Upload Complete';
                            status.style.color = 'var(--color-success)';
                            Vault.toast(`Asset '${file.name}' uploaded successfully.`, 'success');
                        }
                    }
                    setTimeout(() => {
                        window.location.reload();
                    }, 1200);
                } else {
                    if (fill) fill.style.background = 'var(--color-danger)';
                    if (status) {
                        status.textContent = 'Failed: ' + (res.message || 'Server rejected file');
                        status.style.color = 'var(--color-danger)';
                    }
                    Vault.toast(res.message || 'Upload failed', 'danger');
                }
            } catch (err) {
                if (fill) fill.style.background = 'var(--color-danger)';
                if (status) {
                    status.textContent = 'Failed: Invalid response';
                    status.style.color = 'var(--color-danger)';
                }
                Vault.toast('Upload failed: ' + xhr.statusText, 'danger');
            }
        };

        xhr.onerror = () => {
            const fill = itemEl.querySelector('.progress-bar-fill');
            const status = itemEl.querySelector('.status-indicator');
            if (fill) fill.style.background = 'var(--color-danger)';
            if (status) {
                status.textContent = 'Network Error';
                status.style.color = 'var(--color-danger)';
            }
            Vault.toast('Network error during upload', 'danger');
        };

        xhr.send(formData);
    }
};
