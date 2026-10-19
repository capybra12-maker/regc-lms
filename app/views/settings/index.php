<?php
$title = 'System Settings';
$module = 'settings';
$modalRequest = $modalRequest ?? false;
if (!$modalRequest) { require __DIR__ . '/../partials/header.php'; }
require_once __DIR__ . '/../../services/Mailer.php';
$smtpConfig = Mailer::getSmtpConfig();
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
                    <textarea class="form-control" id="company_address" name="company_address" rows="2" maxlength="500"><?= e($settings['company_address'] ?? 'Tagoloan, Misamis Oriental, Philippines'); ?></textarea>
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

            <!-- Email / SMTP Delivery Gateway Configuration -->
            <h3 class="section-title mb-3 border-top pt-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <span><i class="bi bi-envelope-at-fill text-warning me-2"></i>Email / SMTP Delivery Gateway</span>
                <?php if ($smtpConfig['is_configured']): ?>
                    <span class="badge bg-success" style="font-size:12px;"><i class="bi bi-check-circle-fill me-1"></i>Active (SMTP)</span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark" style="font-size:12px;"><i class="bi bi-exclamation-triangle-fill me-1"></i>Setup Needed</span>
                <?php endif; ?>
            </h3>
            <p class="text-muted small mb-3">Configure SMTP credentials to automatically send tracking links to customers. Works with Gmail, Google Workspace, Outlook, or private cPanel mail servers.</p>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label" for="mail_host">SMTP Host *</label>
                    <input class="form-control font-monospace" type="text" id="mail_host" name="mail_host"
                           value="<?= e($settings['mail_host'] ?? ($smtpConfig['host'] ?: 'smtp.gmail.com')); ?>"
                           placeholder="smtp.gmail.com" required maxlength="190">
                    <small class="text-muted">For Gmail, use <code>smtp.gmail.com</code>.</small>
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="mail_port">SMTP Port *</label>
                    <input class="form-control font-monospace" type="number" id="mail_port" name="mail_port"
                           value="<?= e((string) ($settings['mail_port'] ?? ($smtpConfig['port'] ?: 587))); ?>"
                           placeholder="587" required min="1" max="65535">
                    <small class="text-muted">587 for TLS, 465 for SSL.</small>
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="mail_encryption">Encryption *</label>
                    <?php $activeEnc = strtolower($settings['mail_encryption'] ?? ($smtpConfig['encryption'] ?: 'tls')); ?>
                    <select class="form-select" id="mail_encryption" name="mail_encryption">
                        <option value="tls" <?= $activeEnc === 'tls' ? 'selected' : ''; ?>>TLS (STARTTLS - Port 587)</option>
                        <option value="ssl" <?= $activeEnc === 'ssl' ? 'selected' : ''; ?>>SSL (SMTPS - Port 465)</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="mail_username">SMTP Username / Email *</label>
                    <input class="form-control" type="email" id="mail_username" name="mail_username"
                           value="<?= e($settings['mail_username'] ?? ($smtpConfig['username'] ?: '')); ?>"
                           placeholder="your-email@gmail.com" maxlength="190">
                    <small class="text-muted">Your full email address used to log into SMTP.</small>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="mail_password">
                        SMTP Password / App Password
                        <?php if (!empty($settings['mail_password']) || (!empty($smtpConfig['password']) && !$smtpConfig['is_configured'])): ?>
                            <span class="badge bg-light text-dark border ms-1">Password Saved</span>
                        <?php endif; ?>
                    </label>
                    <input class="form-control font-monospace" type="password" id="mail_password" name="mail_password"
                           placeholder="<?= (!empty($settings['mail_password']) || !empty($smtpConfig['password'])) ? '•••••••••••••••• (Leave blank to keep existing)' : 'Enter 16-character App Password'; ?>"
                           autocomplete="new-password">
                    <small class="text-muted">For Gmail, use a <strong>16-letter Google App Password</strong>.</small>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="mail_from">From Email Address</label>
                    <input class="form-control" type="email" id="mail_from" name="mail_from"
                           value="<?= e($settings['mail_from'] ?? ($smtpConfig['from'] ?: '')); ?>"
                           placeholder="dispatch@regctrucking.com" maxlength="190">
                    <small class="text-muted">Sender address shown to customers (usually matches your SMTP email).</small>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="mail_from_name">From Sender Name</label>
                    <input class="form-control" type="text" id="mail_from_name" name="mail_from_name"
                           value="<?= e($settings['mail_from_name'] ?? ($smtpConfig['from_name'] ?: 'REGC Fleet Management')); ?>"
                           placeholder="REGC Fleet Management" maxlength="100">
                </div>
            </div>

            <!-- Gmail Instructions Collapsible Card -->
            <div class="alert alert-light border rounded-3 p-3 mb-4" style="background:#f8fafc;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <strong class="text-dark small"><i class="bi bi-google text-danger me-1"></i> How to set up Gmail in 60 seconds:</strong>
                    <a class="badge bg-secondary text-decoration-none" data-bs-toggle="collapse" href="#gmailGuideCollapse" role="button" aria-expanded="false" aria-controls="gmailGuideCollapse">
                        Show Instructions
                    </a>
                </div>
                <div class="collapse show" id="gmailGuideCollapse">
                    <ol class="small text-muted ps-3 mb-2" style="line-height:1.7;">
                        <li>Enable <strong>2-Step Verification</strong> on your Google Account (<a href="https://myaccount.google.com/security" target="_blank">myaccount.google.com/security</a>).</li>
                        <li>Go to <strong>App Passwords</strong> (<a href="https://myaccount.google.com/apppasswords" target="_blank">myaccount.google.com/apppasswords</a>).</li>
                        <li>Type an app name like <code>REGC LMS</code> and click <strong>Create</strong>.</li>
                        <li>Copy the <strong>16-character password</strong> (e.g. <code>abcd efgh ijkl mnop</code>) and paste it into the <em>SMTP Password / App Password</em> field above.</li>
                        <li>Click <strong>Save All Settings</strong> below, then test with the <em>Send Test Email</em> box on the right!</li>
                    </ol>
                    <small class="text-primary"><i class="bi bi-info-circle me-1"></i> Note: Google blocks regular passwords on external SMTP. You must use an App Password.</small>
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
                    <dd><span class="badge bg-light text-dark border">v<?= e($settings['app_version'] ?? '2.1.0'); ?> Commercial</span></dd>
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
                <i class="bi bi-envelope-at-fill fs-5 <?= $smtpConfig['is_configured'] ? 'text-success' : 'text-warning'; ?>"></i>
                <strong class="text-dark">Email / SMTP Gateway</strong>
                <?php if ($smtpConfig['is_configured']): ?>
                    <span class="badge bg-success ms-auto">Active</span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark ms-auto">Not Configured</span>
                <?php endif; ?>
            </div>

            <dl class="detail-grid mb-3" style="grid-template-columns: 1fr;">
                <div>
                    <dt>SMTP Host</dt>
                    <dd class="font-monospace small"><?= e($smtpConfig['host']); ?>:<?= e((string)$smtpConfig['port']); ?></dd>
                </div>
                <div>
                    <dt>Encryption</dt>
                    <dd><?= e(strtoupper($smtpConfig['encryption'])); ?></dd>
                </div>
                <div>
                    <dt>From Address</dt>
                    <dd class="small"><?= e($smtpConfig['from'] ?: ($smtpConfig['username'] ?: 'Not set')); ?></dd>
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
                <small class="text-muted" style="font-size:11px;">Sends a verification email to test SMTP settings.</small>
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

