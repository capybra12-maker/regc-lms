<?php
$title = ($id ? 'Edit ' : 'Add ') . strtolower($config['singular']);
$modalRequest = $modalRequest ?? false;
$mapsKey = defined('GOOGLE_MAPS_API_KEY') ? GOOGLE_MAPS_API_KEY : '';
$isShipment = ($module === 'shipments');
if (!$modalRequest) { require __DIR__ . '/../partials/header.php'; }
?>
<div data-modal-panel data-modal-title="<?= e(ucfirst($title)); ?>">
<?php if (!$modalRequest): ?>
<div class="page-heading mb-4"><div><span class="eyebrow"><?= e($config['title']); ?></span><h1><?= e(ucfirst($title)); ?></h1></div><a class="btn btn-outline-secondary" href="index.php?route=<?= e($module); ?>">Back to <?= e(strtolower($config['title'])); ?></a></div>
<?php endif; ?>
<p class="text-muted">Fields marked * are required.</p>
<?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error); ?></div><?php endif; ?>
<form method="post" action="index.php?route=<?= e($module); ?>" class="<?= $modalRequest ? '' : 'workspace-card '; ?>edit-form" data-modal-form>
    <?= csrfField(); ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= $id; ?>">
    <?php if ($isShipment): ?>
    <!-- Hidden coordinate fields for pickup & destination -->
    <input type="hidden" name="pickup_lat" id="pickup_lat" value="<?= e((string)($values['pickup_lat'] ?? '')); ?>">
    <input type="hidden" name="pickup_lng" id="pickup_lng" value="<?= e((string)($values['pickup_lng'] ?? '')); ?>">
    <input type="hidden" name="destination_lat" id="destination_lat" value="<?= e((string)($values['destination_lat'] ?? '')); ?>">
    <input type="hidden" name="destination_lng" id="destination_lng" value="<?= e((string)($values['destination_lng'] ?? '')); ?>">
    <div class="form-guidance mb-4"><strong>Plan the delivery</strong><p class="mb-0">Choose an active customer. Assign an available truck and driver together, or leave both blank to save a pending shipment. Each truck and driver can handle one active shipment at a time.</p></div>
    <?php endif; ?>
    <div class="row g-4">
    <?php foreach ($fields as $key => [$label, $type, $required, $limit]):
        $value = (string) ($values[$key] ?? ($type === 'select' ? $limit[0] : ''));
        if ($type === 'datetime-local' && $value !== '') { $value = str_replace(' ', 'T', substr($value, 0, 16)); }
    ?>
        <div class="<?= $type === 'textarea' ? 'col-12' : 'col-md-6'; ?>">
            <label class="form-label" for="<?= e($key); ?>"><?= e($label); ?><?= $required ? ' *' : ''; ?></label>
            <?php if ($type === 'textarea'): ?>
                <textarea class="form-control" id="<?= e($key); ?>" name="<?= e($key); ?>" rows="3" maxlength="<?= (int) $limit; ?>" <?= $required ? 'required' : ''; ?>><?= e($value); ?></textarea>
            <?php elseif ($type === 'select'): ?>
                <select class="form-select" id="<?= e($key); ?>" name="<?= e($key); ?>" <?= $lockedStatus && $key === 'status' ? 'disabled' : ''; ?>>
                    <?php foreach ($limit as $option): ?><option value="<?= e($option); ?>" <?= $value === $option ? 'selected' : ''; ?>><?= e(ucwords(str_replace('_', ' ', $option))); ?></option><?php endforeach; ?>
                </select>
                <?php if ($lockedStatus && $key === 'status'): ?><input type="hidden" name="status" value="<?= e($value); ?>"><p class="form-text">Status is managed by the active shipment.</p><?php endif; ?>
            <?php elseif (isset($choices[$type])): ?>
                <select class="form-select" id="<?= e($key); ?>" name="<?= e($key); ?>" <?= $required ? 'required' : ''; ?> <?= $type === 'truck' ? 'data-truck-select' : ''; ?>>
                    <option value=""><?= $required ? 'Choose a customer' : 'Unassigned'; ?></option>
                    <?php foreach ($choices[$type] as $option):
                        $selected = $value === (string) $option['id'];
                        $available = $option['status'] === ($type === 'customer' ? 'active' : 'available');
                        if (!$available && !$selected) { continue; }
                        $optionLabel = match ($type) {
                            'customer' => ($option['company_name'] ? $option['company_name'] . ' — ' : '') . $option['contact_person'],
                            'truck' => $option['plate_number'] . ' · ' . number_format((float) $option['capacity_kg'], 2) . ' kg',
                            'driver' => $option['full_name'] . ' · ' . $option['license_number'],
                        };
                    ?><option value="<?= (int) $option['id']; ?>" <?= $selected ? 'selected' : ''; ?> <?= $type === 'truck' ? 'data-capacity="' . e($option['capacity_kg']) . '"' : ''; ?>><?= e($optionLabel . (!$available ? ' (' . str_replace('_', ' ', $option['status']) . ')' : '')); ?></option><?php endforeach; ?>
                </select>
                <?php if (!$choices[$type]): ?><p class="form-text">No records yet. <a href="index.php?route=<?= ['customer' => 'customers', 'truck' => 'trucks', 'driver' => 'drivers'][$type]; ?>&amp;action=new">Add a <?= e($type); ?></a> first.</p><?php endif; ?>
            <?php elseif ($isShipment && in_array($key, ['pickup_location', 'destination'], true)): ?>
                <!-- Location Search with Real-Time Autocomplete -->
                <div class="position-relative">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-geo-alt text-warning"></i></span>
                        <input class="form-control border-start-0 regc-places-input"
                               type="text"
                               id="<?= e($key); ?>"
                               name="<?= e($key); ?>"
                               value="<?= e($value); ?>"
                               <?= $required ? 'required' : ''; ?>
                               maxlength="<?= (int) $limit; ?>"
                               placeholder="Type city, port, wharf, or warehouse address..."
                               autocomplete="off"
                               data-lat-target="<?= $key === 'pickup_location' ? 'pickup_lat' : 'destination_lat'; ?>"
                               data-lng-target="<?= $key === 'pickup_location' ? 'pickup_lng' : 'destination_lng'; ?>">
                    </div>
                    <div class="places-suggestions-list list-group position-absolute w-100 shadow-sm" style="display:none; z-index: 1050; max-height: 220px; overflow-y: auto; top: 100%; left: 0;"></div>
                </div>
                <p class="form-text text-muted mb-0" style="font-size: 11px;">
                    <i class="bi bi-search me-1 text-primary"></i>Live address search &mdash; type to auto-fill exact GPS coordinates.
                </p>
            <?php else: ?>
                <input class="form-control" type="<?= e($type); ?>" id="<?= e($key); ?>" name="<?= e($key); ?>" value="<?= e($value); ?>" <?= $required ? 'required' : ''; ?> <?= $type === 'number' ? 'min="0.01" step="0.01" max="9999999999.99"' : ''; ?> <?= in_array($type, ['text', 'email'], true) ? 'maxlength="' . (int) $limit . '"' : ''; ?>>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    </div>
    <div class="form-actions"><button class="btn btn-regc" type="submit">Save <?= e(strtolower($config['singular'])); ?></button><a class="btn btn-outline-secondary" href="index.php?route=<?= e($module); ?>" data-modal-close>Cancel</a></div>
</form>
</div>

<?php if ($isShipment): ?>
<script>
(function() {
    var hasGoogleKey = <?= json_encode((bool) $mapsKey); ?>;
    var googleKey = <?= json_encode($mapsKey); ?>;

    function initGooglePlaces() {
        document.querySelectorAll('.regc-places-input').forEach(function(input) {
            var latTarget = document.getElementById(input.dataset.latTarget);
            var lngTarget = document.getElementById(input.dataset.lngTarget);
            var ac = new google.maps.places.Autocomplete(input, {
                componentRestrictions: { country: 'ph' },
                fields: ['geometry', 'formatted_address']
            });
            ac.addListener('place_changed', function() {
                var place = ac.getPlace();
                if (place.geometry && place.geometry.location) {
                    if (latTarget) latTarget.value = place.geometry.location.lat().toFixed(8);
                    if (lngTarget) lngTarget.value = place.geometry.location.lng().toFixed(8);
                }
            });
            input.addEventListener('input', function() {
                if (latTarget) latTarget.value = '';
                if (lngTarget) lngTarget.value = '';
            });
        });
    }

    // Free OpenStreetMap Nominatim Live Autocomplete (Zero API Key needed)
    function initFreePlaces() {
        document.querySelectorAll('.regc-places-input').forEach(function(input) {
            var latTarget = document.getElementById(input.dataset.latTarget);
            var lngTarget = document.getElementById(input.dataset.lngTarget);
            var listEl = input.closest('.position-relative')?.querySelector('.places-suggestions-list');
            var timer = null;

            if (!listEl) return;

            input.addEventListener('input', function() {
                var query = input.value.trim();
                clearTimeout(timer);

                if (latTarget) latTarget.value = '';
                if (lngTarget) lngTarget.value = '';

                if (query.length < 2) {
                    listEl.style.display = 'none';
                    listEl.innerHTML = '';
                    return;
                }

                timer = setTimeout(function() {
                    fetch('https://nominatim.openstreetmap.org/search?format=json&countrycodes=ph&limit=5&q=' + encodeURIComponent(query), {
                        headers: { 'Accept': 'application/json' }
                    })
                    .then(r => r.json())
                    .then(data => {
                        listEl.innerHTML = '';
                        if (!data || data.length === 0) {
                            listEl.style.display = 'none';
                            return;
                        }
                        data.forEach(function(item) {
                            var btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'list-group-item list-group-item-action text-start p-2 small';
                            btn.innerHTML = '<i class="bi bi-geo-alt-fill text-warning me-1"></i> <strong>' +
                                (item.name || item.display_name.split(',')[0]) + '</strong> <span class="text-muted d-block text-truncate" style="font-size:10.5px;">' +
                                item.display_name + '</span>';

                            btn.addEventListener('click', function() {
                                input.value = item.display_name.split(',').slice(0, 3).join(', ');
                                if (latTarget) latTarget.value = parseFloat(item.lat).toFixed(8);
                                if (lngTarget) lngTarget.value = parseFloat(item.lon).toFixed(8);
                                listEl.style.display = 'none';
                            });

                            listEl.appendChild(btn);
                        });
                        listEl.style.display = 'block';
                    })
                    .catch(function() {
                        listEl.style.display = 'none';
                    });
                }, 350);
            });

            // Close dropdown on outside click
            document.addEventListener('click', function(e) {
                if (!input.contains(e.target) && !listEl.contains(e.target)) {
                    listEl.style.display = 'none';
                }
            });
        });
    }

    if (hasGoogleKey) {
        if (typeof google !== 'undefined' && google.maps && google.maps.places) {
            initGooglePlaces();
        } else {
            var s = document.createElement('script');
            s.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(googleKey) + '&libraries=places&callback=regcInitPlaces';
            s.async = true;
            window.regcInitPlaces = initGooglePlaces;
            document.head.appendChild(s);
        }
    } else {
        initFreePlaces();
    }
})();
</script>
<?php endif; ?>

<?php if (!$modalRequest) { require __DIR__ . '/../partials/footer.php'; } ?>
