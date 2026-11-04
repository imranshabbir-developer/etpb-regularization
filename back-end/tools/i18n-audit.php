<?php

/*
 | Urdu / English translation audit.
 |
 |   php tools/i18n-audit.php                 audit every screen view and app/ PHP file
 |   php tools/i18n-audit.php path [path...]  audit only the given files (relative to repo root)
 |
 | Reports two things:
 |   1. MISSING  — a __('...') / @lang / trans() key that has no Urdu entry in lang/ur.json
 |                 (or lang/parts/*.json while translation work is in progress).
 |   2. ENGLISH  — visible English text in a Blade view that is not wrapped in __()
 |                 (heuristic: text between tags and the placeholder/title/aria-label/alt
 |                 attributes, after Blade echoes, directives, scripts and styles are removed).
 |
 | Exit code is 1 when anything is reported, so it can gate a build.
 */

$root = dirname(__DIR__, 2);
$backEnd = $root.'/back-end';

$ur = [];
$jsonFiles = array_merge([$backEnd.'/lang/ur.json'], glob($backEnd.'/lang/parts/*.json') ?: []);
foreach ($jsonFiles as $file) {
    if (! is_file($file)) {
        continue;
    }
    $decoded = json_decode(file_get_contents($file), true);
    if (! is_array($decoded)) {
        fwrite(STDERR, "INVALID JSON: {$file} — ".json_last_error_msg()."\n");
        exit(2);
    }
    $ur += $decoded;
}

// Exports render through Dompdf, which cannot shape Urdu, so they stay English.
$skip = ['/front-end/views/reports/print/', '/front-end/views/layouts/print.blade.php', '/front-end/views/partials/icon.blade.php'];

$files = [];
if ($argc > 1) {
    foreach (array_slice($argv, 1) as $arg) {
        $path = is_file($arg) ? realpath($arg) : realpath($root.'/'.ltrim($arg, '/\\'));
        if ($path) {
            $files[] = $path;
        } else {
            fwrite(STDERR, "Not found: {$arg}\n");
        }
    }
} else {
    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/front-end/views'));
    foreach ($iter as $f) {
        if ($f->isFile() && str_ends_with($f->getFilename(), '.blade.php')) {
            $files[] = $f->getPathname();
        }
    }
    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($backEnd.'/app'));
    foreach ($iter as $f) {
        if ($f->isFile() && str_ends_with($f->getFilename(), '.php')) {
            $files[] = $f->getPathname();
        }
    }
}

$norm = fn (string $p) => str_replace('\\', '/', $p);
$files = array_values(array_filter($files, function ($f) use ($skip, $norm) {
    foreach ($skip as $s) {
        if (str_contains($norm($f), $s)) {
            return false;
        }
    }

    return true;
}));
sort($files);

function extract_keys(string $src): array
{
    $keys = [];
    $pattern = '/(?<![\w>$])(?:__|trans|@lang)\(\s*(?:\'((?:[^\'\\\\]|\\\\.)*)\'|"((?:[^"\\\\]|\\\\.)*)")/s';
    if (preg_match_all($pattern, $src, $m, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
        foreach ($m as $hit) {
            if (isset($hit[2]) && $hit[2][1] >= 0 && $hit[2][0] !== '') {
                $key = stripcslashes($hit[2][0]);
            } else {
                $key = str_replace(["\\'", '\\\\'], ["'", '\\'], $hit[1][0]);
            }
            $line = substr_count(substr($src, 0, $hit[0][1]), "\n") + 1;
            $keys[] = [$key, $line];
        }
    }

    return $keys;
}

function blank_preserving_lines(string $s): string
{
    return preg_replace('/[^\n]/', ' ', $s);
}

function english_leaks(string $src): array
{
    $s = $src;
    $strip = [
        '/\{\{--.*?--\}\}/s',
        '/<!--.*?-->/s',
        '/<script\b.*?<\/script>/is',
        '/<style\b.*?<\/style>/is',
        '/@php\b.*?@endphp/s',
        '/\{!!.*?!!\}/s',
        '/\{\{.*?\}\}/s',
        '/@(?:verbatim)\b.*?@endverbatim/s',
    ];
    foreach ($strip as $re) {
        $s = preg_replace_callback($re, fn ($m) => blank_preserving_lines($m[0]), $s);
    }
    // Blade directives with balanced parentheses, e.g. @include('x', ['a' => 'b']).
    $s = preg_replace_callback('/@[a-zA-Z]+\s*(\((?:[^()]|(?1))*\))?/', fn ($m) => blank_preserving_lines($m[0]), $s);

    $leaks = [];
    $report = function (string $text, int $offset) use (&$leaks, $s) {
        $text = trim(preg_replace('/\s+/', ' ', html_entity_decode($text, ENT_QUOTES | ENT_HTML5)));
        if ($text === '' || ! preg_match('/[A-Za-z]{2,}/', $text)) {
            return;
        }
        // Things that are legitimately Latin in an Urdu UI.
        if (preg_match('/^(?:[A-Z0-9][A-Z0-9.\-\/]*|Rs\.?|PDF|Excel|Word|CNIC|ETPB|SLA|ID|No\.?|e\.g\.|vs\.?|N\/A|[\w.+-]+@[\w.-]+|https?:\/\/\S+|[a-z]+\.[a-z]+(?:\.[a-z]+)*)$/u', $text)) {
            return;
        }
        $line = substr_count(substr($s, 0, $offset), "\n") + 1;
        $leaks[] = [$text, $line];
    };

    if (preg_match_all('/>([^<>]+)</', $s, $m, PREG_OFFSET_CAPTURE)) {
        foreach ($m[1] as [$text, $off]) {
            $report($text, $off);
        }
    }
    if (preg_match_all('/\b(?:placeholder|title|aria-label|alt|data-confirm|label)\s*=\s*"([^"]*)"/i', $s, $m, PREG_OFFSET_CAPTURE)) {
        foreach ($m[1] as [$text, $off]) {
            $report($text, $off);
        }
    }

    return $leaks;
}

$missing = 0;
$leaksTotal = 0;
foreach ($files as $file) {
    $src = file_get_contents($file);
    $rel = ltrim(str_replace($norm($root), '', $norm($file)), '/');
    $out = [];

    foreach (extract_keys($src) as [$key, $line]) {
        if (! array_key_exists($key, $ur)) {
            $out[] = "  MISSING  L{$line}: {$key}";
            $missing++;
        }
    }
    if (str_ends_with($file, '.blade.php')) {
        foreach (english_leaks($src) as [$text, $line]) {
            $out[] = "  ENGLISH  L{$line}: {$text}";
            $leaksTotal++;
        }
    }
    if ($out) {
        echo $rel."\n".implode("\n", $out)."\n";
    }
}

echo "\n".count($files)." files audited — {$missing} missing Urdu keys, {$leaksTotal} unwrapped English strings.\n";
exit(($missing + $leaksTotal) > 0 ? 1 : 0);
