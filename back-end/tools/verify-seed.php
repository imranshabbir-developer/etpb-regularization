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
$secretary = DB::table('users')->where('email', 'secretary@etpb.gov.pk')->exists();
$chairman = DB::table('users')->where('email', 'chairman@etpb.gov.pk')->exists();
$demo = DB::table('users')->where('email', 'demo.applicant@example.com')->exists();

echo "users={$users}\n";
echo "applications={$apps}\n";
echo "roles={$roles}\n";
echo 'secretary=' . ($secretary ? 'yes' : 'no') . "\n";
echo 'chairman=' . ($chairman ? 'yes' : 'no') . "\n";
echo 'demo_applicant=' . ($demo ? 'yes' : 'no') . "\n";

$ok = $users >= 11 && $apps >= 20 && $roles >= 9 && $secretary && $chairman && $demo;

if (! $ok) {
    fwrite(STDERR, "Seed verification FAILED — re-run: php artisan migrate:fresh --seed --force\n");
    exit(1);
}

echo "Seed verification OK — database is ready for demo.\n";
exit(0);
