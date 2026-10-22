/**
 * REGC LMS — Commercial Logistics Management Application Script
 * Robust, accessible modal engine, real-time validations, animations & notification polling.
 */

document.addEventListener("DOMContentLoaded", () => {
    // ------------------------------------------------------------
    // 1. Password Toggle
    // ------------------------------------------------------------
    document.querySelectorAll('[data-password-toggle]').forEach(button => {
        button.addEventListener('click', () => {
            const input = button.closest('.input-group')?.querySelector('input');
            if (!input) return;
            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            button.setAttribute('aria-pressed', String(!showing));
            button.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
            const icon = button.querySelector('i');
            if (icon) icon.className = showing ? 'bi bi-eye' : 'bi bi-eye-slash';
            input.focus();
        });
    });

    // ------------------------------------------------------------
    // 2. Mobile Sidebar & Backdrop Toggle
    // ------------------------------------------------------------
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.querySelector('[data-sidebar-toggle]');
    const backdrop = document.querySelector('.sidebar-backdrop');

    function openSidebar() {
        if (!sidebar) return;
        sidebar.classList.add('sidebar-mobile-open');
        if (backdrop) backdrop.classList.add('active');
        document.body.classList.add('modal-open');
    }

    function closeSidebar() {
        if (!sidebar) return;
        sidebar.classList.remove('sidebar-mobile-open');
        if (backdrop) backdrop.classList.remove('active');
        document.body.classList.remove('modal-open');
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (sidebar?.classList.contains('sidebar-mobile-open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', closeSidebar);
    }

    // ------------------------------------------------------------
    // 3. Topbar Scroll Effect
    // ------------------------------------------------------------
    const topbar = document.querySelector('.topbar');
    if (topbar) {
        window.addEventListener('scroll', () => {
            topbar.classList.toggle('scrolled', window.scrollY > 10);
        }, { passive: true });
    }

    // ------------------------------------------------------------
    // 4. Truck Capacity Warning & Live Feedback
    // ------------------------------------------------------------
    function bindCapacity(root) {
        const truck = root.querySelector('[data-truck-select]');
        const weight = root.querySelector('#cargo_weight_kg');
        const hint = root.querySelector('#capacity-hint');
        if (!truck || !weight || !hint) return;

        const updateCapacity = () => {
            const capacity = Number(truck.selectedOptions[0]?.dataset.capacity || 0);
            const cargoWeight = Number(weight.value || 0);
            const overloaded = capacity > 0 && cargoWeight > capacity;

            hint.classList.remove('text-danger', 'text-success', 'text-muted');
            if (capacity > 0) {
                if (overloaded) {
                    hint.innerHTML = `<span class="text-danger fw-bold"><i class="bi bi-exclamation-triangle-fill me-1"></i> Warning: Cargo weight (${cargoWeight.toLocaleString()} kg) exceeds this truck's capacity (${capacity.toLocaleString()} kg). Please select another truck.</span>`;
                } else {
                    const remaining = capacity - cargoWeight;
                    hint.innerHTML = `<span class="text-success"><i class="bi bi-check-circle me-1"></i> Capacity: ${capacity.toLocaleString()} kg (${remaining.toLocaleString()} kg available).</span>`;
                }
            } else {
                hint.className = 'form-text text-muted';
                hint.textContent = 'Select a truck to verify capacity against cargo weight.';
            }
        };

        truck.addEventListener('change', updateCapacity);
        weight.addEventListener('input', updateCapacity);
        updateCapacity();
    }
    bindCapacity(document);

    // ------------------------------------------------------------
    // 5. Real-Time Diesel Cost Calculator
    // ------------------------------------------------------------
    function bindDieselCalc(root) {
        const liters = root.querySelector('#liters');
        const price = root.querySelector('#price_per_liter');
        const total = root.querySelector('#total_cost');
        if (!liters || !price) return;

        const updateCost = () => {
            const l = parseFloat(liters.value) || 0;
            const p = parseFloat(price.value) || 0;
            const cost = (l * p).toFixed(2);
            if (total) {
                total.value = cost;
            }
        };

        liters.addEventListener('input', updateCost);
        price.addEventListener('input', updateCost);
        updateCost();
    }
    bindDieselCalc(document);

    // ------------------------------------------------------------
    // 5b. Client-Side Image Compression & Base64 Encoder
    // ------------------------------------------------------------
    async function fileToDataUrl(file) {
        if (!file) return '';
        if (file.type === 'application/pdf') {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = () => resolve(reader.result);
                reader.onerror = reject;
                reader.readAsDataURL(file);
            });
        }
        return new Promise((resolve) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = new Image();
                img.onload = () => {
                    const maxDim = 1600;
                    let w = img.width;
                    let h = img.height;
                    if (w > maxDim || h > maxDim) {
                        if (w > h) {
                            h = Math.round((h * maxDim) / w);
                            w = maxDim;
                        } else {
                            w = Math.round((w * maxDim) / h);
                            h = maxDim;
                        }
                    }
                    const canvas = document.createElement('canvas');
                    canvas.width = w;
                    canvas.height = h;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, w, h);
                    resolve(canvas.toDataURL('image/jpeg', 0.82));
                };
                img.onerror = () => resolve(e.target.result);
                img.src = e.target.result;
            };
            reader.onerror = () => resolve('');
            reader.readAsDataURL(file);
        });
    }

    function bindFileUploads(root) {
        const fileInputs = root.querySelectorAll('input[type="file"][data-base64-target]');
        fileInputs.forEach(input => {
            const targetId = input.dataset.base64Target;
            const hiddenInput = root.querySelector('#' + targetId) || document.getElementById(targetId);
            if (!hiddenInput) return;

            input.addEventListener('change', async () => {
                const file = input.files[0];
                if (!file) {
                    hiddenInput.value = '';
                    return;
                }
                const smallHint = input.parentElement.querySelector('.form-text');
                const origHint = smallHint ? smallHint.textContent : '';
                if (smallHint) {
                    smallHint.innerHTML = '<span class="text-primary"><span class="spinner-border spinner-border-sm me-1" style="width:12px;height:12px;"></span> Optimizing photo…</span>';
                }
                try {
                    const dataUrl = await fileToDataUrl(file);
                    hiddenInput.value = dataUrl;
                    if (smallHint) {
                        const kb = Math.round((dataUrl.length * 0.75) / 1024);
                        smallHint.innerHTML = `<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i> Ready (${kb} KB)</span>`;
                    }
                } catch (err) {
                    if (smallHint) smallHint.textContent = origHint;
                }
            });
        });
    }
    bindFileUploads(document);

    // ------------------------------------------------------------
    // 6. Toast Notification Engine
    // ------------------------------------------------------------
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.style.pointerEvents = 'none';
        document.body.appendChild(toastContainer);
    }

    function showToast(title, message, type = 'info', link = null, duration = 6000) {
        const toast = document.createElement('div');
        toast.className = `regc-toast toast-${type}`;

        const iconMap = {
            success: 'bi-check-circle-fill text-success',
            warning: 'bi-exclamation-triangle-fill text-warning',
            danger: 'bi-x-circle-fill text-danger',
            info: 'bi-info-circle-fill text-primary',
        };
        const iconClass = iconMap[type] || iconMap.info;

        toast.innerHTML = `
            <i class="bi ${iconClass} regc-toast-icon"></i>
            <div class="regc-toast-content">
                <strong>${escapeHtml(title)}</strong>
                <p>${escapeHtml(message)}</p>
                ${link ? `<a href="${escapeHtml(link)}" class="small record-link mt-1 d-inline-block">View Details &rarr;</a>` : ''}
            </div>
            <button type="button" class="regc-toast-close" aria-label="Close">&times;</button>
        `;

        toast.querySelector('.regc-toast-close')?.addEventListener('click', () => {
            dismissToast(toast);
        });

        toastContainer.appendChild(toast);

        if (duration > 0) {
            setTimeout(() => {
                dismissToast(toast);
            }, duration);
        }
    }

    function dismissToast(toast) {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(40px)';
        setTimeout(() => {
            toast.remove();
        }, 300);
    }

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    window.regcToast = showToast;

    // Auto-dismiss standard bootstrap flash alerts after 5 seconds
    document.querySelectorAll('.dashboard-area .alert').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-8px)';
            setTimeout(() => alert.remove(), 400);
        }, 5000);
    });

    // ------------------------------------------------------------
    // 7. Modal Engine (Native <dialog> with AJAX loading)
    // ------------------------------------------------------------
    const modal = document.getElementById('operation-modal');
    const content = document.getElementById('operation-modal-content');
    const title = document.getElementById('operation-modal-title');
    const errorBox = document.getElementById('operation-modal-error');
    const supportsModal = modal && typeof modal.showModal === 'function';
    let loadingRequest = null;
    let saving = false;
    let opener = null;

    function clearError() {
        if (!errorBox) return;
        errorBox.hidden = true;
        errorBox.replaceChildren();
    }

    function showError(message, login = null) {
        if (!errorBox) return;
        errorBox.textContent = message;
        if (login) {
            const link = document.createElement('a');
            link.href = 'index.php?route=login';
            link.textContent = ' Sign in again';
            errorBox.append(link);
        }
        errorBox.hidden = false;
        errorBox.focus();
    }

    function showModal(trigger, heading, compact = false) {
        if (saving || !modal) return false;
        loadingRequest?.abort();
        opener = trigger;
        clearError();
        if (title) title.textContent = heading;
        modal.classList.toggle('operation-modal-small', compact);
        if (content) content.replaceChildren();
        if (!modal.open) modal.showModal();
        document.body.classList.add('modal-open');
        title?.focus();
        return true;
    }

    function setSaving(value) {
        saving = value;
        if (!modal) return;
        modal.setAttribute('aria-busy', String(value));
        modal.querySelectorAll('button[type="submit"], button[data-modal-close]').forEach(button => {
            button.disabled = value;
        });
    }

    function installPanel(panel) {
        if (title) title.textContent = panel.dataset.modalTitle || 'Edit Record';
        if (content) {
            content.replaceChildren(document.importNode(panel, true));
            bindCapacity(content);
            bindDieselCalc(content);
            bindFileUploads(content);
            const invalid = content.querySelector('[role="alert"]');
            if (invalid) {
                invalid.tabIndex = -1;
                invalid.focus();
            } else {
                const first = content.querySelector('input:not([type="hidden"]), select, textarea');
                (first || title).focus();
            }
        }
    }

    async function readResponse(response) {
        const text = await response.text();
        if ((response.headers.get('content-type') || '').includes('application/json')) {
            try {
                return { json: JSON.parse(text) };
            } catch (e) {
                return { message: text.slice(0, 300) };
            }
        }
        const doc = new DOMParser().parseFromString(text, 'text/html');
        return {
            panel: doc.querySelector('[data-modal-panel]'),
            message: doc.body.textContent.trim().slice(0, 500)
        };
    }

    function finishSave(redirect) {
        const destination = new URL(redirect, window.location.href);
        if (destination.origin !== window.location.origin) throw new Error('Unexpected destination.');
        window.location.assign(destination.href);
    }

    async function openForm(link) {
        if (!showModal(link, 'Loading form…')) return;
        const loader = document.createElement('div');
        loader.className = 'text-center py-5';
        loader.innerHTML = `
            <div class="modal-spinner mb-3"></div>
            <p class="text-muted mb-0">Loading form, please wait…</p>
        `;
        content.append(loader);

        const request = new AbortController();
        loadingRequest = request;

        try {
            const response = await fetch(link.href, {
                headers: { 'X-Requested-With': 'REGC-Modal' },
                signal: request.signal,
                credentials: 'same-origin',
                cache: 'no-store'
            });
            const result = await readResponse(response);
            if (request.signal.aborted || !modal.open) return;
            content.replaceChildren();

            if (response.ok && result.panel) {
                installPanel(result.panel);
            } else if (response.ok && result.json?.redirect) {
                finishSave(result.json.redirect);
            } else {
                title.textContent = 'Unable to open form';
                showError(result.json?.message || result.message || 'Unable to load form. Close dialog and try again.', result.json?.login);
            }
        } catch (error) {
            if (error.name === 'AbortError') return;
            content.replaceChildren();
            title.textContent = 'Unable to open form';
            showError('The form could not be loaded. Please check your connection.');
        }
    }

    function confirmDelete(form, trigger) {
        const actionAttr = (form.getAttribute('action') || '');
        const isDelete = actionAttr.includes('delete') || (form.dataset.confirm || '').toLowerCase().includes('delete');
        const heading = isDelete ? 'Confirm Deletion' : 'Please Confirm';
        if (!showModal(trigger, heading, true)) return;
        const container = document.createElement('div');
        container.className = 'text-center py-2';

        const warningIcon = document.createElement('div');
        warningIcon.className = `mb-3 ${isDelete ? 'text-danger' : 'text-primary'}`;
        warningIcon.innerHTML = `<i class="bi ${isDelete ? 'bi-exclamation-triangle-fill' : 'bi-question-circle-fill'}" style="font-size: 48px;"></i>`;

        const prompt = document.createElement('h5');
        prompt.className = 'fw-bold mb-2';
        prompt.textContent = isDelete ? 'Are you sure you want to delete this record?' : 'Confirm this operational action';

        const recordDesc = document.createElement('p');
        recordDesc.className = 'text-muted small mb-4';
        recordDesc.textContent = form.dataset.confirm || 'This action cannot be undone.';

        const actions = document.createElement('div');
        actions.className = 'd-flex justify-content-center gap-2';

        const confirmBtn = document.createElement('button');
        confirmBtn.type = 'button';
        confirmBtn.className = `btn ${isDelete ? 'btn-danger' : 'btn-regc'} px-4`;
        confirmBtn.textContent = isDelete ? 'Yes, Delete' : 'Yes, Proceed';
        confirmBtn.addEventListener('click', () => {
            form.removeAttribute('data-confirm');
            HTMLFormElement.prototype.submit.call(form);
        });

        const cancelBtn = document.createElement('button');
        cancelBtn.type = 'button';
        cancelBtn.className = 'btn btn-outline-secondary px-4';
        cancelBtn.setAttribute('data-modal-close', '');
        cancelBtn.textContent = 'Cancel';

        actions.append(cancelBtn, confirmBtn);
        container.append(warningIcon, prompt, recordDesc, actions);
        content.append(container);
        cancelBtn.focus();
    }

    if (supportsModal) {
        modal.addEventListener('cancel', event => {
            if (saving) event.preventDefault();
        });

        modal.addEventListener('close', () => {
            loadingRequest?.abort();
            document.body.classList.remove('modal-open');
            content.replaceChildren();
            clearError();
            if (opener?.isConnected) opener.focus();
        });

        document.addEventListener('click', event => {
            const close = event.target.closest('[data-modal-close]');
            if (close && modal.contains(close)) {
                event.preventDefault();
                if (!saving) modal.close();
                return;
            }

            const link = event.target.closest('a[data-modal-link]');
            if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            openForm(link);
        });
    }

    document.addEventListener('submit', async event => {
        const form = event.target;
        if (form.matches('form[data-confirm]')) {
            if (supportsModal) {
                event.preventDefault();
                confirmDelete(form, event.submitter || form.querySelector('button'));
            } else if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
            return;
        }

        if (!supportsModal || !modal.contains(form) || !form.matches('[data-modal-form]')) return;
        event.preventDefault();
        if (saving || !form.reportValidity()) return;

        clearError();

        // Process any pending file inputs that haven't finished converting to base64 yet
        const pendingFiles = form.querySelectorAll('input[type="file"][data-base64-target]');
        for (const input of pendingFiles) {
            const targetId = input.dataset.base64Target;
            const hidden = form.querySelector('#' + targetId) || document.getElementById(targetId);
            if (hidden && !hidden.value && input.files[0]) {
                hidden.value = await fileToDataUrl(input.files[0]);
            }
        }

        setSaving(true);
        const submit = form.querySelector('button[type="submit"]');
        const originalLabel = submit?.innerHTML;
        if (submit) submit.innerHTML = '<span class="modal-spinner me-2" style="width: 14px; height: 14px; border-width: 2px;"></span> Saving…';

        let completed = false;
        const formAction = form.getAttribute('action') || window.location.href;
        const formMethod = (form.getAttribute('method') || 'POST').toUpperCase();

        // Use URLSearchParams when base64 file inputs are present to send application/x-www-form-urlencoded.
        // This avoids OpenResty 403 Forbidden firewall blocks on multipart/form-data.
        let bodyPayload;
        const headers = { 'X-Requested-With': 'REGC-Modal' };
        const hasBase64 = form.querySelectorAll('input[type="hidden"][name$="_base64"]').length > 0;

        if (hasBase64) {
            const params = new URLSearchParams();
            const formData = new FormData(form);
            for (const [key, value] of formData.entries()) {
                if (!(value instanceof File)) {
                    params.append(key, value);
                }
            }
            bodyPayload = params;
            headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8';
        } else {
            bodyPayload = new FormData(form);
        }

        try {
            const response = await fetch(formAction, {
                method: formMethod,
                body: bodyPayload,
                credentials: 'same-origin',
                headers: headers
            });
            const result = await readResponse(response);
            if (response.ok && result.json?.redirect) {
                finishSave(result.json.redirect);
                completed = true;
            } else if (response.status === 422 && result.panel) {
                installPanel(result.panel);
            } else {
                showError(result.json?.message || result.message || 'The change could not be saved. Please review your entries.', result.json?.login);
            }
        } catch (error) {
            showError('Could not confirm whether the change was saved. Please check your connection.');
        } finally {
            if (!completed) setSaving(false);
            if (submit?.isConnected && originalLabel) submit.innerHTML = originalLabel;
        }
    });

    // ------------------------------------------------------------
    // 8. Eased Animated Number Counters
    // ------------------------------------------------------------
    const counters = document.querySelectorAll(".counter");
    counters.forEach(counter => {
        const target = Number(counter.dataset.value);
        if (isNaN(target)) return;
        const duration = 750;
        const startTime = performance.now();

        function easeOutCubic(x) {
            return 1 - Math.pow(1 - x, 3);
        }

        function animate(time) {
            const elapsed = time - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const eased = easeOutCubic(progress);
            const value = Math.floor(eased * target);

            counter.textContent = value.toLocaleString();

            if (progress < 1) {
                requestAnimationFrame(animate);
            } else {
                counter.textContent = target.toLocaleString();
            }
        }

        requestAnimationFrame(animate);
    });

    // ------------------------------------------------------------
    // 9. Notification Background Polling
    // ------------------------------------------------------------
    const notifBadge = document.querySelector('.notif-badge');
    if (notifBadge && document.querySelector('.topbar')) {
        let lastCount = parseInt(notifBadge.textContent) || 0;

        async function pollNotifications() {
            try {
                const res = await fetch('index.php?route=notifications-poll', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) return;
                const data = await res.json();
                const unread = data.unread_count || 0;

                if (unread > 0) {
                    notifBadge.textContent = unread > 99 ? '99+' : unread;
                    notifBadge.style.display = 'flex';
                    // If new notifications arrived, show a subtle toast
                    if (unread > lastCount && data.recent && data.recent[0]) {
                        const latest = data.recent[0];
                        showToast(latest.title, latest.message, latest.type || 'info', latest.link);
                    }
                } else {
                    notifBadge.style.display = 'none';
                }
                lastCount = unread;
            } catch (e) {
                // Ignore silent network failure
            }
        }

        // Poll every 45 seconds
        setInterval(pollNotifications, 45000);
    }
});
