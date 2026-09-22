<?php

/**
 * Smoke-test every report format (pdf/docx/xlsx) for key registers and
 * board reports so a demo laptop never ships a broken export path.
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\Models\User::where('email', 'secretary@etpb.gov.pk')->first()
    ?? App\Models\User::where('email', 'chairman@etpb.gov.pk')->firstOrFail();
auth()->login($user);

$controller = $app->make(App\Http\Controllers\ReportController::class);
$outDir = storage_path('app/report-smoke');
if (! is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}

$jobs = [
    ['glimpse', fn ($fmt) => Illuminate\Http\Request::create('/reports/glimpse', 'GET', ['format' => $fmt]),
        fn ($req) => $controller->glimpse($req)],
    ['executive', fn ($fmt) => Illuminate\Http\Request::create('/reports/executive', 'GET', ['format' => $fmt]),
        fn ($req) => $controller->executive($req)],
];

foreach (['fee', 'applications', 'assessment', 'notices', 'arrears', 'litigation', 'hearings', 'agreements', 'objections', 'regularized'] as $reg) {
    $jobs[] = [
        "register-{$reg}",
        fn ($fmt) => Illuminate\Http\Request::create("/reports/registers/{$reg}", 'GET', ['format' => $fmt]),
        fn ($req) => $controller->register($req, $reg),
    ];
}

$appModel = App\Models\Application::query()->orderBy('id')->first();
if ($appModel) {
    $jobs[] = [
        'deep',
        fn ($fmt) => Illuminate\Http\Request::create('/applications/' . $appModel->id . '/report', 'GET', ['format' => $fmt]),
        function ($req) use ($controller, $appModel) {
            return $controller->deep($req, $appModel);
        },
    ];
}

$failed = 0;
foreach ($jobs as [$name, $makeReq, $call]) {
    foreach (['pdf', 'docx', 'xlsx'] as $fmt) {
        try {
            $req = $makeReq($fmt);
            $req->setUserResolver(fn () => $user);
            $response = $call($req);
            ob_start();
            $response->sendContent();
            $bin = ob_get_clean();
            $path = "{$outDir}/{$name}.{$fmt}";
            file_put_contents($path, $bin);
            $size = strlen($bin);
            if ($size < 500) {
                throw new RuntimeException("tiny output ({$size} bytes)");
            }
            echo "OK  {$name}.{$fmt} ({$size} bytes)\n";
        } catch (Throwable $e) {
            $failed++;
            echo "FAIL {$name}.{$fmt}: " . $e->getMessage() . "\n";
        }
    }
}

exit($failed > 0 ? 1 : 0);
