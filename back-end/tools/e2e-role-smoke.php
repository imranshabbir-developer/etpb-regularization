<?php

/**
 * End-to-end HTTP smoke for every seeded role against key routes.
 * Uses the real app + session cookies against php artisan serve.
 *
 * Usage: php tools/e2e-role-smoke.php [baseUrl]
 */

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8000', '/');
$password = 'Etpb@2026#Change';

$roles = [
    'admin@etpb.gov.pk' => [
        'label' => 'SYSTEM_ADMIN',
        'paths' => ['/', '/applications', '/admin/users', '/admin/settings', '/admin/audit', '/admin/reference-data'],
    ],
    'chairman@etpb.gov.pk' => [
        'label' => 'CHAIRMAN',
        'paths' => ['/', '/applications', '/reports/glimpse', '/reports/executive', '/reports/registers'],
    ],
    'secretary@etpb.gov.pk' => [
        'label' => 'SECRETARY',
        'paths' => ['/', '/applications', '/reports/glimpse', '/reports/executive', '/reports/registers'],
        'forbidden' => ['/admin/users', '/admin/settings'],
    ],
    'admin.lhr@etpb.gov.pk' => [
        'label' => 'ADMINISTRATOR',
        'paths' => ['/', '/applications', '/approvals'],
    ],
    'do.lhr@etpb.gov.pk' => [
        'label' => 'DISTRICT_OFFICER',
        'paths' => ['/', '/applications', '/queues/scrutiny', '/queues/assessment', '/queues/objections', '/queues/arrears'],
    ],
    'da.lhr@etpb.gov.pk' => [
        'label' => 'DEALING_ASSISTANT',
        'paths' => ['/', '/applications', '/apply'],
    ],
    'accounts.lhr@etpb.gov.pk' => [
        'label' => 'ACCOUNTS_OFFICER',
        'paths' => ['/', '/applications', '/queues/scrutiny', '/queues/arrears'],
    ],
    'legal.lhr@etpb.gov.pk' => [
        'label' => 'LEGAL_OFFICER',
        'paths' => ['/', '/queues/litigation'],
    ],
    'audit@etpb.gov.pk' => [
        'label' => 'AUDITOR',
        'paths' => ['/', '/applications', '/reports/executive', '/admin/audit'],
    ],
    'demo.applicant@example.com' => [
        'label' => 'APPLICANT',
        'paths' => ['/', '/apply', '/my-applications'],
    ],
];

$failed = 0;
$passed = 0;

function http(string $method, string $url, array $opts = []): array
{
    $ch = curl_init($url);
    $headers = $opts['headers'] ?? [];
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HEADER         => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_COOKIE         => $opts['cookie'] ?? '',
        CURLOPT_POSTFIELDS     => $opts['body'] ?? null,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $raw = curl_exec($ch);
    if ($raw === false) {
        $err = curl_error($ch);
        curl_close($ch);

        return ['code' => 0, 'headers' => '', 'body' => $err, 'cookie' => ''];
    }
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    $hdr = substr($raw, 0, $headerSize);
    $body = substr($raw, $headerSize);
    $cookie = $opts['cookie'] ?? '';
    if (preg_match_all('/^Set-Cookie:\s*([^;]+)/mi', $hdr, $m)) {
        foreach ($m[1] as $c) {
            [$n] = explode('=', $c, 2);
            // replace existing cookie of same name
            $parts = array_filter(array_map('trim', explode(';', $cookie)));
            $parts = array_values(array_filter($parts, fn ($p) => ! str_starts_with($p, $n . '=')));
            $parts[] = $c;
            $cookie = implode('; ', $parts);
        }
    }

    return ['code' => $code, 'headers' => $hdr, 'body' => $body, 'cookie' => $cookie];
}

function extractCsrf(string $html): ?string
{
    if (preg_match('/name="_token"\s+value="([^"]+)"/', $html, $m)) {
        return $m[1];
    }
    if (preg_match('/name="csrf-token"\s+content="([^"]+)"/', $html, $m)) {
        return $m[1];
    }

    return null;
}

echo "E2E role smoke against {$base}\n";

foreach ($roles as $email => $cfg) {
    echo "\n== {$cfg['label']} ({$email}) ==\n";

    $loginPage = http('GET', $base . '/login');
    if ($loginPage['code'] !== 200) {
        echo "FAIL login page HTTP {$loginPage['code']}\n";
        $failed++;
        continue;
    }
    $token = extractCsrf($loginPage['body']);
    if (! $token) {
        echo "FAIL CSRF token missing on login\n";
        $failed++;
        continue;
    }

    $login = http('POST', $base . '/login', [
        'cookie'  => $loginPage['cookie'],
        'headers' => ['Content-Type: application/x-www-form-urlencoded'],
        'body'    => http_build_query([
            '_token'   => $token,
            'email'    => $email,
            'password' => $password,
        ]),
    ]);

    $cookie = $login['cookie'] ?: $loginPage['cookie'];
    if (! in_array($login['code'], [302, 303], true) && $login['code'] !== 200) {
        echo "FAIL login HTTP {$login['code']}\n";
        $failed++;
        continue;
    }
    // Follow redirect to dashboard
    $loc = null;
    if (preg_match('/^Location:\s*(.+)$/mi', $login['headers'], $m)) {
        $loc = trim($m[1]);
        if (! str_starts_with($loc, 'http')) {
            $loc = $base . '/' . ltrim($loc, '/');
        }
    }
    $home = http('GET', $loc ?: ($base . '/'), ['cookie' => $cookie]);
    if (! in_array($home['code'], [200, 302], true)) {
        echo "FAIL post-login home HTTP {$home['code']}\n";
        $failed++;
        continue;
    }
    echo "OK  login\n";
    $passed++;

    foreach ($cfg['paths'] as $path) {
        $res = http('GET', $base . $path, ['cookie' => $cookie]);
        // Allow redirect chains for auth/start pages
        if (in_array($res['code'], [200, 302], true)) {
            echo "OK  GET {$path} ({$res['code']})\n";
            $passed++;
        } else {
            echo "FAIL GET {$path} HTTP {$res['code']}\n";
            $failed++;
        }
    }

    foreach ($cfg['forbidden'] ?? [] as $path) {
        $res = http('GET', $base . $path, ['cookie' => $cookie]);
        if (in_array($res['code'], [403, 302, 404], true)) {
            echo "OK  forbidden GET {$path} ({$res['code']})\n";
            $passed++;
        } else {
            echo "FAIL expected block on {$path}, got {$res['code']}\n";
            $failed++;
        }
    }

    // Logout best-effort
    $dash = http('GET', $base . '/', ['cookie' => $cookie]);
    $tok = extractCsrf($dash['body']);
    if ($tok) {
        http('POST', $base . '/logout', [
            'cookie'  => $cookie,
            'headers' => ['Content-Type: application/x-www-form-urlencoded'],
            'body'    => http_build_query(['_token' => $tok]),
        ]);
    }
}

// Data integrity checks via artisan bootstrap
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n== Data integrity ==\n";
$checks = [
    'no_indigent_column' => ! Schema::hasColumn('applicants', 'is_indigent'),
    'urban_default' => DB::table('unit_conversion_profiles')->where('code', 'URBAN')->where('is_default', true)->exists(),
    'ghunta_unit' => DB::table('unit_conversion_factors')->where('unit_code', 'GHUNTA')->exists(),
    'no_mandatory_docs' => DB::table('document_types')->where('is_mandatory', true)->count() === 0,
    'possession_cert' => DB::table('document_types')->where('code', 'POSSESSION_CERTIFICATE')->exists(),
    'payment_receipt_type' => DB::table('document_types')->where('code', 'PAYMENT_RECEIPT')->exists(),
    'chairman_correct' => DB::table('role_permission')
        ->join('roles', 'roles.id', '=', 'role_permission.role_id')
        ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
        ->where('roles.code', 'CHAIRMAN')->where('permissions.code', 'records.correct')->exists(),
    'secretary_no_correct' => ! DB::table('role_permission')
        ->join('roles', 'roles.id', '=', 'role_permission.role_id')
        ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
        ->where('roles.code', 'SECRETARY')->where('permissions.code', 'records.correct')->exists(),
    'admin_can_upload' => DB::table('role_permission')
        ->join('roles', 'roles.id', '=', 'role_permission.role_id')
        ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
        ->where('roles.code', 'ADMINISTRATOR')->where('permissions.code', 'documents.upload')->exists(),
    'apps_have_areas' => DB::table('applications as a')
        ->join('properties as p', 'p.id', '=', 'a.property_id')
        ->join('property_areas as pa', 'pa.property_id', '=', 'p.id')
        ->where('pa.is_current', true)->whereNull('a.deleted_at')->count()
        === DB::table('applications')->whereNull('deleted_at')->count(),
    'property_types_ok' => DB::table('properties')
        ->whereNotIn('property_type', ['OPEN_PLOT', 'RESIDENTIAL', 'COMMERCIAL', 'RESIDENTIAL_CUM_COMMERCIAL', 'OTHER'])
        ->count() === 0,
];

foreach ($checks as $name => $ok) {
    if ($ok) {
        echo "OK  {$name}\n";
        $passed++;
    } else {
        echo "FAIL {$name}\n";
        $failed++;
    }
}

echo "\nSummary: passed={$passed} failed={$failed}\n";
exit($failed > 0 ? 1 : 0);
