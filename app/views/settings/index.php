<?php
$title = 'System Settings';
$module = 'settings';
$modalRequest = $modalRequest ?? false;
if (!$modalRequest) { require __DIR__ . '/../partials/header.php'; }
?>

<div data-modal-panel data-modal-title="Company &amp; Fleet Settings">
<?php if (!$modalRequest): ?>
<div class="page-heading mb-4">
    <div>
        <span class="eyebrow">Administration &amp; Preferences</span>
        <h1>System Settings</h1>
        <p>Manage company identity, commercial fuel baselines, and application configuration.</p>
    </div>
</div>
<?php endif; ?>

<div class="row g-4">
    <div class="<?= $modalRequest ? 'col-12' : 'col-lg-8'; ?>">
        <form method="post" action="index.php?route=settings-save" class="<?= $modalRequest ? '' : 'workspace-card '; ?>edit-form" data-modal-form>
            <?= csrfField(); ?>
            <h3 class="section-title mb-4">Company Profile</h3>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label" for="company_name">Company Name *</label>
                    <input class="form-control" type="text" id="company_name" name="company_name" value="<?= e($settings['company_name'] ?? 'REGC Fleet Management'); ?>" required maxlength="180">
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="company_tagline">Company Tagline</label>
                    <input class="form-control" type="text" id="company_tagline" name="company_tagline" value="<?= e($settings['company_tagline'] ?? 'Track. Deliver. Manage. Efficiently.'); ?>" maxlength="200">
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="company_email">Dispatch Email Address</label>
                    <input class="form-control" type="email" id="company_email" name="company_email" value="<?= e($settings['company_email'] ?? 'dispatch@regctrucking.com'); ?>" maxlength="190">
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="company_phone">Commercial Telephone / Mobile</label>
                    <input class="form-control" type="text" id="company_phone" name="company_phone" value="<?= e($settings['company_phone'] ?? '+63 (083) 552-0000'); ?>" maxlength="50">
                </div>

                <div class="col-12">
                    <label class="form-label" for="company_address">Depot &amp; Office Address</label>
                    <textarea class="form-control" id="company_address" name="company_address" rows="2" maxlength="500"><?= e($settings['company_address'] ?? 'General Santos City, South Cotabato, Philippines'); ?></textarea>
                </div>
            </div>

            <h3 class="section-title mb-3 border-top pt-4">Fleet Fuel Configuration</h3>
            <p class="text-muted small mb-3">Set benchmark parameters for diesel monitoring across all operational routes.</p>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label" for="fuel_baseline_liters">Initial Fuel Baseline (Liters/Trip) *</label>
                    <input class="form-control" type="number" step="0.1" min="1" id="fuel_baseline_liters" name="fuel_baseline_liters" value="<?= e($settings['fuel_baseline_liters'] ?? '15'); ?>" required>
                    <small class="form-text text-muted">Standard target threshold per dispatched transit (15 L baseline).</small>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="fuel_price_per_liter">Default Diesel Price per Liter (₱) *</label>
                    <input class="form-control" type="number" step="0.01" min="1" id="fuel_price_per_liter" name="fuel_price_per_liter" value="<?= e($settings['fuel_price_per_liter'] ?? '65.00'); ?>" required>
                    <small class="form-text text-muted">Auto-populates new fuel logging forms.</small>
                </div>
            </div>

            <div class="form-actions mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-regc">
                    <i class="bi bi-check-lg"></i> Save All Settings
                </button>
                <?php if ($modalRequest): ?>
                    <button type="button" class="btn btn-outline-secondary" data-modal-close>Cancel</button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php if (!$modalRequest): ?>
    <div class="col-lg-4">
        <div class="workspace-card mb-4">
            <h4 class="fw-bold mb-3 text-dark">System Information</h4>
            <dl class="detail-grid" style="grid-template-columns: 1fr;">
                <div>
                    <dt>Application Version</dt>
                    <dd><span class="badge bg-light text-dark border">v<?= e($settings['app_version'] ?? '2.0.0'); ?> Commercial</span></dd>
                </div>
                <div>
                    <dt>PHP Engine</dt>
                    <dd>PHP <?= PHP_VERSION; ?></dd>
                </div>
                <div>
                    <dt>Database Server</dt>
                    <dd>MySQL 8.x / MariaDB</dd>
                </div>
                <div>
                    <dt>Timezone</dt>
                    <dd><?= e(APP_TIMEZONE); ?> (<?= date('T P'); ?>)</dd>
                </div>
                <div>
                    <dt>Current Time</dt>
                    <dd><?= date('M d, Y &middot; g:i:s A'); ?></dd>
                </div>
            </dl>
        </div>

        <!-- Email / SMTP Configuration Status -->
        <div class="workspace-card mb-4">
            <div class="d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-envelope-at-fill fs-5 <?= (defined('MAIL_HOST') && MAIL_HOST !== '') ? 'text-success' : 'text-warning'; ?>"></i>
                <strong class="text-dark">Email / SMTP</strong>
                <?php if (defined('MAIL_HOST') && MAIL_HOST !== '' && defined('MAIL_USERNAME') && MAIL_USERNAME !== '' && MAIL_USERNAME !== 'your-gmail@gmail.com'): ?>
                    <span class="badge bg-success ms-auto">Configured</span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark ms-auto">Not Configured</span>
                <?php endif; ?>
            </div>

            <?php if (defined('MAIL_HOST') && MAIL_HOST !== ''): ?>
                <dl class="detail-grid mb-3" style="grid-template-columns: 1fr;">
                    <div>
                        <dt>SMTP Host</dt>
                        <dd class="font-monospace small"><?= e(MAIL_HOST); ?>:<?= e(MAIL_PORT); ?></dd>
                    </div>
                    <div>
                        <dt>Encryption</dt>
                        <dd><?= e(strtoupper(MAIL_ENCRYPTION)); ?></dd>
                    </div>
                    <div>
                        <dt>From Address</dt>
                        <dd class="small"><?= e(MAIL_FROM ?: MAIL_USERNAME); ?></dd>
                    </div>
                </dl>

                <!-- Test Email Panel -->
                <div class="border-top pt-3">
                    <label class="form-label small fw-bold text-muted">Send Test Email</label>
                    <div class="input-group input-group-sm">
                        <input type="email" class="form-control" id="test-email-to"
                               placeholder="recipient@email.com"
                               value="<?= e(authUser()['email'] ?? ''); ?>">
                        <button class="btn btn-outline-primary btn-sm" type="button" id="btn-send-test-email">
                            <i class="bi bi-send me-1"></i>Send
                        </button>
                    </div>
                    <div id="test-email-result" class="mt-2 small d-none"></div>
                </div>

                <script>
                document.getElementById('btn-send-test-email').addEventListener('click', function() {
                    var to = document.getElementById('test-email-to').value.trim();
                    var resultEl = document.getElementById('test-email-result');
                    if (!to) { alert('Enter a recipient email first.'); return; }
                    this.disabled = true;
                    this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Sending…';
                    fetch('index.php?route=mail-test&to=' + encodeURIComponent(to))
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            resultEl.className = 'mt-2 small alert ' + (data.ok ? 'alert-success' : 'alert-danger') + ' py-2';
                            resultEl.textContent = data.message;
                            resultEl.classList.remove('d-none');
                            var btn = document.getElementById('btn-send-test-email');
                            btn.disabled = false;
                            btn.innerHTML = '<i class="bi bi-send me-1"></i>Send';
                        })
                        .catch(function() {
                            resultEl.className = 'mt-2 small alert alert-danger py-2';
                            resultEl.textContent = 'Network error — could not reach server.';
                            resultEl.classList.remove('d-none');
                            var btn = document.getElementById('btn-send-test-email');
                            btn.disabled = false;
                            btn.innerHTML = '<i class="bi bi-send me-1"></i>Send';
                        });
                });
                </script>
            <?php else: ?>
                <p class="text-muted small mb-3">Email delivery is disabled. Add your SMTP credentials to the <code>.env</code> file to enable real email sending.</p>
                <div class="bg-light rounded-2 p-3 border small font-monospace" style="font-size: 11px; line-height: 1.8;">
                    <div class="text-muted">Add to <strong>.env</strong>:</div>
                    MAIL_HOST=smtp.gmail.com<br>
                    MAIL_PORT=587<br>
                    MAIL_ENCRYPTION=tls<br>
                    MAIL_USERNAME=you@gmail.com<br>
                    MAIL_PASSWORD=your-app-password<br>
                    MAIL_FROM=you@gmail.com
                </div>
                <a href="https://myaccount.google.com/apppasswords" target="_blank" class="btn btn-outline-secondary btn-sm mt-3 w-100">
                    <i class="bi bi-google me-1"></i> Get Gmail App Password
                </a>
            <?php endif; ?>
        </div>


        <!-- SMS / Text Message Configuration -->
        <div class="workspace-card mb-4">
            <div class="d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-phone-fill fs-5 <?= (defined('SMS_API_KEY') && SMS_API_KEY !== '' && SMS_API_KEY !== 'your-semaphore-api-key') ? 'text-success' : 'text-warning'; ?>"></i>
                <strong class="text-dark">SMS / Text Message</strong>
                <?php if (defined('SMS_API_KEY') && SMS_API_KEY !== '' && SMS_API_KEY !== 'your-semaphore-api-key'): ?>
                    <span class="badge bg-success ms-auto">Configured</span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark ms-auto">Not Configured</span>
                <?php endif; ?>
            </div>

            <?php if (defined('SMS_API_KEY') && SMS_API_KEY !== '' && SMS_API_KEY !== 'your-semaphore-api-key'): ?>
                <dl class="detail-grid mb-3" style="grid-template-columns: 1fr;">
                    <div>
                        <dt>Provider</dt>
                        <dd><?= e(strtoupper(SMS_PROVIDER)); ?> (Philippine Gateway)</dd>
                    </div>
                    <div>
                        <dt>Sender Name</dt>
                        <dd class="font-monospace"><?= e(SMS_SENDER_NAME); ?></dd>
                    </div>
                </dl>

                <!-- SMS Test Panel -->
                <div class="border-top pt-3">
                    <label class="form-label small fw-bold text-muted">Send Test SMS</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">📱</span>
                        <input type="tel" class="form-control" id="test-sms-to"
                               placeholder="09XXXXXXXXX"
                               pattern="[0-9+]{11,13}">
                        <button class="btn btn-outline-success btn-sm" type="button" id="btn-send-test-sms">
                            <i class="bi bi-send me-1"></i>Send SMS
                        </button>
                    </div>
                    <small class="text-muted" style="font-size:11px;">Enter your own phone number to test.</small>
                    <div id="test-sms-result" class="mt-2 small d-none"></div>
                </div>

                <script>
                document.getElementById('btn-send-test-sms').addEventListener('click', function() {
                    var to = document.getElementById('test-sms-to').value.trim();
                    var resultEl = document.getElementById('test-sms-result');
                    if (!to) { alert('Enter a phone number first (e.g. 09123456789).'); return; }
                    this.disabled = true;
                    this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Sending…';
                    fetch('index.php?route=sms-test&to=' + encodeURIComponent(to))
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            resultEl.className = 'mt-2 small alert ' + (data.ok ? 'alert-success' : 'alert-danger') + ' py-2';
                            resultEl.textContent = data.message;
                            resultEl.classList.remove('d-none');
                            var btn = document.getElementById('btn-send-test-sms');
                            btn.disabled = false;
                            btn.innerHTML = '<i class="bi bi-send me-1"></i>Send SMS';
                        })
                        .catch(function() {
                            resultEl.className = 'mt-2 small alert alert-danger py-2';
                            resultEl.textContent = 'Network error — could not reach server.';
                            resultEl.classList.remove('d-none');
                            var btn = document.getElementById('btn-send-test-sms');
                            btn.disabled = false;
                            btn.innerHTML = '<i class="bi bi-send me-1"></i>Send SMS';
                        });
                });
                </script>
            <?php else: ?>
                <p class="text-muted small mb-3">SMS delivery is not enabled. Sign up for a free Semaphore account and add your API key to <code>.env</code>.</p>
                <div class="bg-light rounded-2 p-3 border small font-monospace" style="font-size:11px; line-height:1.8;">
                    <div class="text-muted">Add to <strong>.env</strong>:</div>
                    SMS_PROVIDER=semaphore<br>
                    SMS_API_KEY=your-key-here<br>
                    SMS_SENDER_NAME=REGCLMS
                </div>
                <a href="https://semaphore.co" target="_blank" class="btn btn-outline-success btn-sm mt-3 w-100">
                    <i class="bi bi-phone me-1"></i> Sign Up Free at Semaphore.co
                </a>
                <small class="text-muted d-block mt-1 text-center" style="font-size:11px;">1,000 SMS/month free — no credit card</small>
            <?php endif; ?>
        </div>

        <div class="workspace-card bg-light border">
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-cloud-check-fill text-success fs-5"></i>
                <strong class="text-dark">Cloud-Ready Infrastructure</strong>
            </div>
            <p class="text-muted small mb-0">
                REGC LMS is structured for zero-downtime shared/cloud deployment with relative asset paths, environment-based configuration, strict CSRF validation, and parameterized SQL queries.
            </p>
        </div>
    </div>
    <?php endif; ?>

</div>
</div>

<?php if (!$modalRequest) { require __DIR__ . '/../partials/footer.php'; } ?>

