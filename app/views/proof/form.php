<?php
$title = 'Proof of Delivery (POD)';
$modalRequest = $modalRequest ?? false;
if (!$modalRequest) { require __DIR__ . '/../partials/header.php'; }
?>

<div data-modal-panel data-modal-title="Proof of Delivery &mdash; <?= e($shipment['tracking_number']); ?>">
<?php if (!$modalRequest): ?>
<div class="page-heading mb-4">
    <div><span class="eyebrow">Shipment Confirmation</span><h1>Proof of Delivery</h1></div>
    <a class="btn btn-outline-secondary" href="index.php?route=shipments&amp;action=show&amp;id=<?= (int) $shipment['id']; ?>">&larr; Back to Shipment</a>
</div>
<?php endif; ?>

<div class="form-guidance mb-4">
    <strong>Verify Receiving Handover</strong>
    <p class="mb-0">Upload delivery receipt, photograph of delivered cargo, or signed acceptance documentation to clear shipment <strong><?= e($shipment['tracking_number']); ?></strong>.</p>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger mb-4" role="alert"><?= e($error); ?></div>
<?php endif; ?>

<div id="pod-form-error" class="alert alert-danger mb-4" style="display:none;" role="alert"></div>

<form method="post" action="index.php?route=proof-upload" id="pod-upload-form" class="<?= $modalRequest ? '' : 'workspace-card '; ?>edit-form">
    <?= csrfField(); ?>
    <input type="hidden" name="shipment_id" value="<?= (int) $shipment['id']; ?>">
    <input type="hidden" name="proof_image_base64" id="proof_image_base64">
    <input type="hidden" name="delivery_receipt_base64" id="delivery_receipt_base64">
    <input type="hidden" name="signature_file_base64" id="signature_file_base64">

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="received_by">Recipient Full Name *</label>
            <input class="form-control" type="text" id="received_by" name="received_by" value="<?= e($values['received_by'] ?? ''); ?>" required maxlength="150" placeholder="e.g. Warehouse Manager Name" autofocus>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="proof_image">Cargo Photo / Delivery Photo</label>
            <input class="form-control" type="file" id="proof_image" accept="image/*,.pdf" data-base64-target="proof_image_base64">
            <small class="form-text text-muted">Accepts JPG, PNG, WEBP, or PDF</small>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="delivery_receipt">Signed Delivery Receipt (DR)</label>
            <input class="form-control" type="file" id="delivery_receipt" accept="image/*,.pdf" data-base64-target="delivery_receipt_base64">
            <small class="form-text text-muted">Scan or photo of signed physical paper DR</small>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="signature_file">Recipient Signature Image (Optional)</label>
            <input class="form-control" type="file" id="signature_file" accept="image/*" data-base64-target="signature_file_base64">
            <small class="form-text text-muted">Digital signature capture or photo</small>
        </div>

        <div class="col-12">
            <label class="form-label" for="notes">Condition / Handover Remarks</label>
            <textarea class="form-control" id="notes" name="notes" rows="3" maxlength="2000" placeholder="Confirm seal number, bag counts, cargo condition upon arrival..."><?= e($values['notes'] ?? ''); ?></textarea>
        </div>

        <?php if ($shipment['current_status'] === 'arrived'): ?>
        <div class="col-12">
            <div class="form-check p-3 rounded bg-light border">
                <input class="form-check-input" type="checkbox" id="mark_delivered" name="mark_delivered" value="1" checked>
                <label class="form-check-label fw-bold text-dark" for="mark_delivered">
                    Mark Shipment as "Delivered" upon submission
                </label>
                <small class="d-block text-muted">Releases assigned truck (<?= e($shipment['plate_number'] ?? 'N/A'); ?>) and driver to available status.</small>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="form-actions mt-4">
        <button class="btn btn-regc" type="submit" id="btn-submit-pod">
            <i class="bi bi-cloud-arrow-up-fill"></i> Upload Proof of Delivery
        </button>
        <a class="btn btn-outline-secondary" href="index.php?route=shipments&amp;action=show&amp;id=<?= (int) $shipment['id']; ?>" data-modal-close>Cancel</a>
    </div>
</form>

<script>
(function initPodForm() {
    const form = document.getElementById('pod-upload-form');
    if (!form) return;

    function compressFile(file) {
        return new Promise((resolve) => {
            if (!file) return resolve('');
            if (file.type === 'application/pdf') {
                const reader = new FileReader();
                reader.onload = () => resolve(reader.result);
                reader.onerror = () => resolve('');
                reader.readAsDataURL(file);
                return;
            }
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

    form.querySelectorAll('input[type="file"]').forEach(input => {
        const targetId = input.dataset.base64Target;
        const hidden = document.getElementById(targetId);
        const hint = input.parentElement.querySelector('.form-text');
        const origText = hint ? hint.textContent : '';

        input.addEventListener('change', async () => {
            const file = input.files[0];
            if (!file) {
                if (hidden) hidden.value = '';
                if (hint) hint.textContent = origText;
                return;
            }
            if (hint) {
                hint.innerHTML = '<span class="text-primary"><span class="spinner-border spinner-border-sm me-1" style="width:12px;height:12px;"></span> Optimizing photo…</span>';
            }
            const dataUrl = await compressFile(file);
            if (hidden) hidden.value = dataUrl;
            if (hint) {
                const kb = Math.round((dataUrl.length * 0.75) / 1024);
                hint.innerHTML = '<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i> Ready (' + kb + ' KB)</span>';
            }
        });
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (!form.reportValidity()) return;

        const errBox = document.getElementById('pod-form-error');
        if (errBox) {
            errBox.style.display = 'none';
            errBox.textContent = '';
        }

        const submitBtn = document.getElementById('btn-submit-pod') || form.querySelector('button[type="submit"]');
        const origLabel = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Uploading Proof…';

        try {
            for (const input of form.querySelectorAll('input[type="file"]')) {
                const targetId = input.dataset.base64Target;
                const hidden = document.getElementById(targetId);
                if (hidden && !hidden.value && input.files[0]) {
                    hidden.value = await compressFile(input.files[0]);
                }
            }

            const params = new URLSearchParams();
            const formData = new FormData(form);
            for (const [key, value] of formData.entries()) {
                if (!(value instanceof File)) {
                    params.append(key, value);
                }
            }

            const response = await fetch(form.action, {
                method: 'POST',
                body: params.toString(),
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'REGC-Modal',
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                }
            });

            const text = await response.text();
            let result = null;
            try {
                result = JSON.parse(text);
            } catch (pe) {}

            if (response.ok && result && result.redirect) {
                window.location.assign(result.redirect);
                return;
            }

            if (result && result.message) {
                if (errBox) {
                    errBox.textContent = result.message;
                    errBox.style.display = 'block';
                } else {
                    alert(result.message);
                }
            } else if (!response.ok) {
                const doc = new DOMParser().parseFromString(text, 'text/html');
                const msg = doc.body.textContent.trim().slice(0, 300);
                if (errBox) {
                    errBox.textContent = msg || 'Server error. Please try again.';
                    errBox.style.display = 'block';
                }
            }
        } catch (err) {
            if (errBox) {
                errBox.textContent = 'Network error. Please check your internet connection.';
                errBox.style.display = 'block';
            }
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = origLabel;
        }
    });
})();
</script>
</div>

<?php if (!$modalRequest) { require __DIR__ . '/../partials/footer.php'; } ?>

