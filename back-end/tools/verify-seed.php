<?php

/**
 * Post-setup sanity check for a fresh clone.
 * Exit 0 only when the full demo dataset is present.
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$users = (int) DB::table('users')->count();
$apps = (int) DB::table('applications')->count();
$roles = (int) DB::table('roles')->count();
$fees = (int) DB::table('fee_payments')->whereNull('deleted_at')->count();
$perms = (int) DB::table('permissions')->count();
$districts = (int) DB::table('districts')->count();

$requiredEmails = [
    'admin@etpb.gov.pk',
    'chairman@etpb.gov.pk',
    'secretary@etpb.gov.pk',
    'admin.lhr@etpb.gov.pk',
    'do.lhr@etpb.gov.pk',
    'da.lhr@etpb.gov.pk',
    'accounts.lhr@etpb.gov.pk',
    'legal.lhr@etpb.gov.pk',
    'audit@etpb.gov.pk',
    'imran.shabbir@example.com',
    'demo.applicant@example.com',
    'sohan.lal@example.com',
];

$missing = [];
foreach ($requiredEmails as $email) {
    if (! DB::table('users')->where('email', $email)->exists()) {
        $missing[] = $email;
    }
}

echo "users={$users}\n";
echo "applications={$apps}\n";
echo "roles={$roles}\n";
echo "permissions={$perms}\n";
echo "districts={$districts}\n";
echo "fee_payments={$fees}\n";
echo 'accounts_present=' . (count($requiredEmails) - count($missing)) . '/' . count($requiredEmails) . "\n";

$ok = $users >= 12
    && $apps >= 20
    && $roles >= 9
    && $perms >= 40
    && $districts >= 100
    && $fees >= 5
    && $missing === [];

if (! $ok) {
    if ($missing !== []) {
        fwrite(STDERR, 'Missing accounts: ' . implode(', ', $missing) . "\n");
    }
    fwrite(STDERR, "Seed verification FAILED — re-run: php artisan migrate:fresh --seed --force\n");
    exit(1);
}

echo "Seed verification OK — database is ready for demo.\n";
exit(0);
