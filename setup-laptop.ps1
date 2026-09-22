#Requires -Version 5.1
<#
.SYNOPSIS
  One-shot setup for ETPB Regularization on a fresh Windows / XAMPP laptop.

.DESCRIPTION
  After cloning from GitHub, run this once. It:
  - copies .env.example -> .env
  - creates MySQL databases
  - composer install + npm install
  - php artisan key:generate
  - migrate:fresh --seed  (full schema + all demo records + login accounts)
  - npm run build
  - storage:link + cache clear
  - writes deploy/apache-etpb.local.conf with THIS machine's path

.NOTES
  Prerequisites (home laptop):
  - XAMPP with PHP 8.3+ and MySQL started
  - Composer
  - Node.js 20+
  Run:
    powershell -ExecutionPolicy Bypass -File .\setup-laptop.ps1
  Or double-click setup-laptop.bat
#>

$ErrorActionPreference = 'Stop'

$Root = Split-Path -Parent $MyInvocation.MyCommand.Path
$BackEnd = Join-Path $Root 'back-end'
$RootUnix = ($Root -replace '\\', '/')

$PhpCandidates = @(
    'C:\xampp\php\php.exe',
    (Get-Command php -ErrorAction SilentlyContinue | Select-Object -ExpandProperty Source)
) | Where-Object { $_ -and (Test-Path $_) }

$Php = $PhpCandidates | Select-Object -First 1
if (-not $Php) {
    Write-Error 'PHP not found. Install XAMPP (PHP 8.3+) and ensure C:\xampp\php\php.exe exists.'
}

# Prefer XAMPP PHP for Composer / npm child processes in this session.
$env:Path = (Split-Path $Php -Parent) + ';' + $env:Path

$Mysql = 'C:\xampp\mysql\bin\mysql.exe'
if (-not (Test-Path $Mysql)) {
    $MysqlCmd = Get-Command mysql -ErrorAction SilentlyContinue
    if ($MysqlCmd) { $Mysql = $MysqlCmd.Source }
}

$Composer = $null
foreach ($c in @(
    'C:\xampp\php\composer.bat',
    'C:\ProgramData\ComposerSetup\bin\composer.bat',
    (Get-Command composer -ErrorAction SilentlyContinue | Select-Object -ExpandProperty Source)
)) {
    if ($c -and (Test-Path $c)) { $Composer = $c; break }
}
if (-not $Composer) {
    $Phar = 'C:\xampp\php\composer.phar'
    if (Test-Path $Phar) {
        $Composer = $Phar
    } else {
        Write-Error 'Composer not found. Install Composer, then re-run this script.'
    }
}

Write-Host ''
Write-Host '=== ETPB Regularization — laptop setup ===' -ForegroundColor Cyan
Write-Host "PHP:      $Php"
Write-Host "Composer: $Composer"
Write-Host "Root:     $Root"
Write-Host ''

Push-Location $BackEnd
try {
    if (-not (Test-Path '.env')) {
        Copy-Item '.env.example' '.env'
        Write-Host 'Created back-end/.env from .env.example' -ForegroundColor Yellow
        Write-Host 'If MySQL root has a password, set DB_PASSWORD in back-end/.env and re-run.' -ForegroundColor Yellow
    }

    $DbName = 'etpb_regularization'
    $DbUser = 'root'
    $DbPass = ''
    $DbHost = '127.0.0.1'
    foreach ($line in Get-Content '.env') {
        if ($line -match '^\s*DB_DATABASE=(.*)$') { $DbName = $Matches[1].Trim().Trim('"') }
        if ($line -match '^\s*DB_USERNAME=(.*)$') { $DbUser = $Matches[1].Trim().Trim('"') }
        if ($line -match '^\s*DB_PASSWORD=(.*)$') { $DbPass = $Matches[1].Trim().Trim('"') }
        if ($line -match '^\s*DB_HOST=(.*)$') { $DbHost = $Matches[1].Trim().Trim('"') }
    }

    if (Test-Path $Mysql) {
        Write-Host "Ensuring MySQL databases exist on $DbHost..." -ForegroundColor Green
        $passArg = @()
        if ($DbPass -ne '') { $passArg = @("-p$DbPass") }
        & $Mysql -h $DbHost -u $DbUser @passArg -e "CREATE DATABASE IF NOT EXISTS ``$DbName`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
        if ($LASTEXITCODE -ne 0) { throw 'Could not create MySQL database. Is MySQL running in XAMPP?' }
        & $Mysql -h $DbHost -u $DbUser @passArg -e "CREATE DATABASE IF NOT EXISTS ``etpb_regularization_test`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    } else {
        Write-Host 'mysql.exe not found — create database etpb_regularization in phpMyAdmin if migrate fails.' -ForegroundColor Yellow
    }

    # Storage folders Git does not keep empty trees for.
    @(
        'storage\app\public',
        'storage\app\private',
        'storage\framework\cache\data',
        'storage\framework\sessions',
        'storage\framework\views',
        'storage\logs',
        'bootstrap\cache'
    ) | ForEach-Object {
        $p = Join-Path $BackEnd $_
        if (-not (Test-Path $p)) { New-Item -ItemType Directory -Path $p -Force | Out-Null }
    }

    Write-Host 'composer install...' -ForegroundColor Green
    if ($Composer -like '*.phar') {
        & $Php $Composer install --no-interaction
    } else {
        & $Composer install --no-interaction
    }
    if ($LASTEXITCODE -ne 0) { throw 'composer install failed' }

    Write-Host 'Generating APP_KEY...' -ForegroundColor Green
    & $Php artisan key:generate --force
    if ($LASTEXITCODE -ne 0) { throw 'key:generate failed' }

    Write-Host 'migrate:fresh --seed (schema + ALL demo data + login accounts)...' -ForegroundColor Green
    & $Php artisan migrate:fresh --seed --force
    if ($LASTEXITCODE -ne 0) { throw 'migrate:fresh --seed failed' }

    Write-Host 'Verifying seed data...' -ForegroundColor Green
    & $Php tools\verify-seed.php
    if ($LASTEXITCODE -ne 0) { throw 'seed verification failed' }

    Write-Host 'npm install + build...' -ForegroundColor Green
    npm install --ignore-scripts
    if ($LASTEXITCODE -ne 0) { throw 'npm install failed' }
    npm run build
    if ($LASTEXITCODE -ne 0) { throw 'npm run build failed' }

    Write-Host 'storage:link + cache clear...' -ForegroundColor Green
    & $Php artisan storage:link --force 2>$null
    & $Php artisan config:clear
    & $Php artisan view:clear
    & $Php artisan route:clear

    # Apache vhost with THIS clone path (not committed).
    $ApacheLocal = Join-Path $Root 'deploy\apache-etpb.local.conf'
    $PublicPath = "$RootUnix/back-end/public"
    $FrontAssets = "$RootUnix/front-end/assets"
    @"
# AUTO-GENERATED by setup-laptop.ps1 — do not commit.
# Include from XAMPP httpd.conf:
#   Include "$RootUnix/deploy/apache-etpb.local.conf"

Listen 8080

<VirtualHost *:8080>
    ServerName etpb.localhost
    DocumentRoot "$PublicPath"
    <Directory "$PublicPath">
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog  "C:/xampp/apache/logs/etpb-error.log"
    CustomLog "C:/xampp/apache/logs/etpb-access.log" combined
</VirtualHost>

<Directory "$FrontAssets">
    Options -Indexes +FollowSymLinks
    Require all granted
</Directory>
"@ | Set-Content -Path $ApacheLocal -Encoding UTF8

    Write-Host ''
    Write-Host '=== Setup complete — project is ready ===' -ForegroundColor Green
    Write-Host ''
    Write-Host 'EASIEST way to run (no Apache config needed):' -ForegroundColor Cyan
    Write-Host "  cd `"$BackEnd`""
    Write-Host "  `"$Php`" artisan serve --port=8000"
    Write-Host '  then open http://127.0.0.1:8000'
    Write-Host ''
    Write-Host 'OR use Apache on :8080:' -ForegroundColor Cyan
    Write-Host "  1) Add this line to C:\xampp\apache\conf\httpd.conf:"
    Write-Host "     Include `"$RootUnix/deploy/apache-etpb.local.conf`""
    Write-Host '  2) Start Apache in XAMPP'
    Write-Host '  3) Open http://localhost:8080'
    Write-Host ''
    Write-Host 'LOGIN (see ACCOUNTS.md / START_HERE.md for full list):' -ForegroundColor Cyan
    Write-Host '  Officers (all):     Etpb@2026#Change'
    Write-Host '    chairman@etpb.gov.pk       Chairman (charts + reports)'
    Write-Host '    secretary@etpb.gov.pk      Secretary (charts + reports)'
    Write-Host '    admin.lhr@etpb.gov.pk       Administrator'
    Write-Host '    do.lhr@etpb.gov.pk          District Officer'
    Write-Host '    accounts.lhr@etpb.gov.pk    Accounts Officer'
    Write-Host '  Applicants:'
    Write-Host '    demo.applicant@example.com / Demo#Portal2026'
    Write-Host '    imran.shabbir@example.com  / Imran@Portal2026'
    Write-Host '    sohan.lal@example.com      / Sohan#Portal2026'
    Write-Host ''
    Write-Host 'Demo walkthrough: docs\DEMO_FLOW_GUIDE.md'
    Write-Host 'Database was fully seeded (officers + applicants + demo cases + charts assets).'
    Write-Host 'To wipe and reload later:  php artisan migrate:fresh --seed --force'
    Write-Host ''
}
finally {
    Pop-Location
}
