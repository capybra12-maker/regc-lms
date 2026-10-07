<?php
// Read-only page checks, rejected form submissions, and normal login/logout audit entries.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$base = $argv[1] ?? 'http://localhost/regc-lms/index.php';
$curl = curl_init();
curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_COOKIEFILE => '', CURLOPT_TIMEOUT => 15]);
function request(string $query, ?array $data = null, string $method = 'GET', bool $modal = false): array {
    global $curl, $base;
    curl_setopt($curl, CURLOPT_URL, $base . '?' . $query);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, null);
    curl_setopt($curl, CURLOPT_HTTPGET, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $modal ? ['X-Requested-With: REGC-Modal'] : []);
    if ($data !== null) {
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    if (!in_array($method, ['GET', 'POST'], true)) {
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    }
    $body = curl_exec($curl);
    if ($body === false) { throw new RuntimeException(curl_error($curl)); }
    return [curl_getinfo($curl, CURLINFO_HTTP_CODE), $body, curl_getinfo($curl, CURLINFO_EFFECTIVE_URL)];
}
$checks = 0;
function check(bool $condition, string $message): void {
    global $checks;
    if (!$condition) { throw new RuntimeException('FAIL: ' . $message); }
    $checks++;
    echo 'PASS: ', $message, PHP_EOL;
}
[$status, $html] = request('route=landing');
check($status === 200 && !preg_match('/Fatal error|Warning:|Parse error/', $html), 'Public landing page loads without PHP errors');
[$status, $html] = request('route=landing&track%5B%5D=invalid');
check($status === 400 && str_contains($html, 'Invalid tracking number.') && !str_contains($html, 'Warning'), 'Landing rejects malformed tracking input without warnings');
[$status, $html, $url] = request('route=landing&track=%20unknown%20');
check($status === 200 && str_ends_with($url, 'route=tracking&number=UNKNOWN'), 'Landing forwards normalized unknown numbers to tracking results');
[$status, $html, $url] = request('route=customers');
check(str_ends_with($url, 'route=login'), 'Guest management access redirects to login');
preg_match('/name="csrf_token" value="([^"]+)"/', $html, $matches);
$token = $matches[1];
[$status, $html, $url] = request('route=login-submit', ['email' => 'admin@regc.com', 'password' => 'Admin@123456', 'csrf_token' => $token], 'POST');
check($status === 200 && str_ends_with($url, 'route=dashboard'), 'Administrator login works');
check(curl_getinfo($curl, CURLINFO_HTTP_CODE) === 200, 'Application responds normally after login');
try {
    foreach (['dashboard', 'customers', 'trucks', 'drivers', 'shipments', 'tracking', 'account', 'customers&action=new', 'trucks&action=new', 'drivers&action=new', 'shipments&action=new', 'tracking&number=UNKNOWN'] as $route) {
        [$status, $html] = request('route=' . $route);
        check($status === 200 && !preg_match('/Fatal error|Warning:|Parse error/', $html), 'Page loads: ' . $route);
        check(str_contains($html, 'route=tracking') && !str_contains($html, 'disabled-link'), 'Navigation enabled: ' . $route);
    }
    [$status] = request('route=customers', ['action' => 'save', 'contact_person' => 'CSRF test', 'status' => 'active'], 'POST');
    check($status === 403, 'Missing CSRF rejected');
    [$status] = request('route=customers', ['action' => 'save', 'csrf_token' => ['malformed']], 'POST');
    check($status === 403, 'Malformed CSRF rejected');
    [$status, $html] = request('route=customers', ['action' => 'save', 'contact_person' => '', 'email' => 'invalid', 'status' => 'active', 'csrf_token' => $token], 'POST');
    check($status === 422 && str_contains($html, 'Contact person is required.') && str_contains($html, 'value="invalid"'), 'Validation error preserves entered form values');
    [$status] = request('route=shipments', null, 'PUT');
    check($status === 405, 'Unsupported HTTP method rejected');
    [$status] = request('route=shipments&action=show&id=2147483647');
    check($status === 404, 'Missing shipment returns 404');
    [$status] = request('route=customers&action=edit&id=invalid');
    check($status === 400, 'Invalid record ID rejected');
    foreach (['customers', 'trucks', 'drivers', 'shipments'] as $module) {
        [$status, $html] = request('route=' . $module . '&action=new', null, 'GET', true);
        check($status === 200 && str_contains($html, 'data-modal-panel') && str_contains($html, 'data-modal-form') && !str_contains($html, '<html'), 'Modal form fragment: ' . $module);
    }
    [$status, $html] = request('route=account-password', null, 'GET', true);
    check($status === 200 && str_contains($html, 'data-modal-panel') && str_contains($html, 'current_password'), 'Password form loads as a modal fragment');
    [$status, $html] = request('route=account-password', ['current_password' => 'wrong-password', 'password' => 'A-secure-test-passphrase', 'password_confirmation' => 'A-secure-test-passphrase', 'csrf_token' => $token], 'POST', true);
    check($status === 422 && str_contains($html, 'current password is incorrect'), 'Password form rejects an incorrect current password');
    [$status, $html] = request('route=customers', ['action' => 'save', 'contact_person' => '', 'email' => 'retained@example.invalid', 'status' => 'active', 'csrf_token' => $token], 'POST', true);
    check($status === 422 && str_contains($html, 'data-modal-panel') && str_contains($html, 'retained@example.invalid') && !str_contains($html, '<html'), 'Modal validation preserves values without a full page');
    [$status, $json] = request('route=shipments', ['action' => 'status', 'id' => '2147483647', 'status' => 'pickup', 'remarks' => 'Test', 'csrf_token' => $token], 'POST', true);
    check($status === 422 && (json_decode($json, true)['message'] ?? '') === 'Shipment not found.', 'Modal status errors return a JSON message');
    [$status, $json] = request('route=customers', ['action' => 'delete', 'id' => '2147483647', 'csrf_token' => $token], 'POST', true);
    check($status === 422 && isset(json_decode($json, true)['message']), 'Modal delete errors return a JSON message');
    [$status] = request('route=customers', ['action' => 'save'], 'POST', true);
    check($status === 403, 'Modal submissions retain CSRF protection');
} finally {
    request('route=logout', ['csrf_token' => $token], 'POST');
}
[$status, $html, $url] = request('route=shipments');
check(str_ends_with($url, 'route=login'), 'Logout protects operational routes');
[$status, $json] = request('route=customers&action=new', null, 'GET', true);
check($status === 401 && (json_decode($json, true)['login'] ?? '') === 'index.php?route=login', 'Expired modal sessions return a sign-in action');
echo "All {$checks} HTTP checks passed.", PHP_EOL;
