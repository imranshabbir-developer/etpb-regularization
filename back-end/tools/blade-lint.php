<?php

/*
 | Compile the given Blade views and syntax-check the generated PHP.
 |
 |   php tools/blade-lint.php front-end/views/apply/fee.blade.php [...]
 |   php tools/blade-lint.php            (every view under front-end/views)
 */

$root = dirname(__DIR__, 2);
$backEnd = $root.'/back-end';

require $backEnd.'/vendor/autoload.php';
$app = require $backEnd.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$files = array_slice($argv, 1);
if (! $files) {
    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/front-end/views'));
    foreach ($iter as $f) {
        if ($f->isFile() && str_ends_with($f->getFilename(), '.blade.php')) {
            $files[] = $f->getPathname();
        }
    }
}

$php = PHP_BINARY;
$failed = 0;
foreach ($files as $file) {
    $path = is_file($file) ? $file : $root.'/'.ltrim($file, '/\\');
    if (! is_file($path)) {
        echo "NOT FOUND {$file}\n";
        $failed++;
        continue;
    }
    $compiled = Illuminate\Support\Facades\Blade::compileString(file_get_contents($path));
    $tmp = tempnam(sys_get_temp_dir(), 'blade').'.php';
    file_put_contents($tmp, $compiled);
    $out = shell_exec('"'.$php.'" -l '.escapeshellarg($tmp).' 2>&1');
    @unlink($tmp);
    if (! str_contains((string) $out, 'No syntax errors')) {
        echo "FAIL {$file}\n".trim((string) $out)."\n";
        $failed++;
    }
}

echo count($files)." views compiled — {$failed} with errors.\n";
exit($failed ? 1 : 0);
