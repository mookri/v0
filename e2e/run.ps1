# e2e/run.ps1 - production-grade full-stack E2E orchestration.
#
#   frontend (BFF, next start) -> Laravel (artisan serve) -> MySQL 8
#
# Steps: provision e2e DBs -> migrate:fresh -> seed active tenant -> boot
# both servers -> HTTP scenarios (scenarios.mjs, S1-S13) -> restart BFF
# (fresh in-process rate limiters) -> Playwright UI flow -> teardown.
#
# Safety rails: ONLY navdhya_-prefixed databases are ever touched;
# platform-navdhya/.env and the sqlite files are never modified (all
# overrides ride real process env vars, which beat .env immutable loads).
#
# Params:
#   -HttpOnly    skip the Playwright phase
#   -SkipBuild   never build (fail instead if apps/web/.next is missing)
#
# Exit code: 0 iff every phase passed.

param(
  [switch]$HttpOnly,
  [switch]$SkipBuild
)

$ErrorActionPreference = 'Stop'
# next.config.ts re-evaluates `output` at RUNTIME: without this, a .next
# built with NEXT_STANDALONE=0 still makes `next start` refuse to boot
# (standalone config). Set it for every path (build guard + start).
$env:NEXT_STANDALONE = '0'
$Root = Split-Path $PSScriptRoot -Parent
$MysqlExe = 'C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe'
$RootPass = '123456789'
$DbUser = 'navdhya_e2e'
$DbPass = 'navdhya_e2e_2026'
$DbCentral = 'navdhya_e2e_central'
$DbTenant = 'navdhya_e2e_tenant'
$LaravelUrl = 'http://127.0.0.1:8000'
$BffUrl = 'http://127.0.0.1:3000'

function Say([string]$m) { Write-Output "[$(Get-Date -Format HH:mm:ss)] $m" }

function Invoke-Mysql([string]$Sql, [string]$User = 'root', [string]$Pass = $RootPass, [string]$Database = '') {
  $mysqlArgs = @("-u$User", '-B', '-N', '--silent', '-e', $Sql)
  if ($Database) { $mysqlArgs += "-D$Database" }
  # PS 5.1 trap: native stderr + 2>&1 + $ErrorActionPreference='Stop'
  # turns mysql's "password on the command line" warning into a
  # terminating NativeCommandError. MYSQL_PWD keeps the password off
  # argv (no warning emitted at all) and the preference guard makes any
  # other stderr line survivable while still capturing it for errors.
  $prevPwd = $env:MYSQL_PWD
  $prevEap = $ErrorActionPreference
  $env:MYSQL_PWD = $Pass
  $ErrorActionPreference = 'Continue'
  try {
    $out = @(& $MysqlExe @mysqlArgs 2>&1)
    $code = $LASTEXITCODE
  } finally {
    $ErrorActionPreference = $prevEap
    $env:MYSQL_PWD = $prevPwd
  }
  $clean = @($out | Where-Object { "$_" -notmatch 'Warning' })
  if ($code -ne 0) { throw "mysql exited ${code}: $($clean -join "`n")`nSQL: $Sql" }
  return ($clean -join "`n").Trim()
}

function Stop-Port([int]$Port) {
  try {
    $conns = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue
    foreach ($c in $conns) {
      Stop-Process -Id $c.OwningProcess -Force -ErrorAction SilentlyContinue
      Say "  stopped PID $($c.OwningProcess) on :$Port"
    }
    Start-Sleep -Milliseconds 500
  } catch { }
}

function Wait-Http([string]$Url, [int]$TimeoutSec = 90) {
  $deadline = (Get-Date).AddSeconds($TimeoutSec)
  while ((Get-Date) -lt $deadline) {
    try {
      $r = Invoke-WebRequest -Uri $Url -UseBasicParsing -TimeoutSec 3
      if ($r.StatusCode -ge 200 -and $r.StatusCode -lt 600) { return $true }
    } catch {
      # any HTTP response (even 4xx/5xx) proves the port is serving; only
      # connection failures keep looping.
      if ($_.Exception.Response) { return $true }
    }
    Start-Sleep -Milliseconds 700
  }
  return $false
}

# Server logs (both boots) land here for post-mortem: PHP's built-in
# access log carries the backend's real status per request, and Next's
# stderr carries any uncaught route stack.
$LogDir = Join-Path $PSScriptRoot '.logs'

function Start-Laravel {
  $phpArgs = @('artisan', 'serve', '--host=127.0.0.1', '--port=8000')
  Start-Process -FilePath 'php' -ArgumentList $phpArgs `
    -WorkingDirectory (Join-Path $Root 'platform-navdhya') -WindowStyle Hidden `
    -RedirectStandardOutput (Join-Path $LogDir 'laravel-out.log') `
    -RedirectStandardError (Join-Path $LogDir 'laravel-err.log')
}

$script:BffBoot = 0
function Start-Bff {
  $script:BffBoot += 1
  $tag = "bff$($script:BffBoot)"
  $nextBin = Join-Path $Root 'apps\web\node_modules\next\dist\bin\next'
  if (-not (Test-Path $nextBin)) { throw "next binary not found: $nextBin" }
  Start-Process -FilePath 'node' -ArgumentList @($nextBin, 'start', '-p', '3000') `
    -WorkingDirectory (Join-Path $Root 'apps\web') -WindowStyle Hidden `
    -RedirectStandardOutput (Join-Path $LogDir "$tag-out.log") `
    -RedirectStandardError (Join-Path $LogDir "$tag-err.log")
}

# ---------------------------------------------------------------- guard --
Say 'E2E run starting'
New-Item -ItemType Directory -Force -Path $LogDir | Out-Null
# Keep evidence artifacts (perf raw samples/stdout, phpunit logs); only
# rotate this run's server/scenario logs.
Get-ChildItem $LogDir -File -ErrorAction SilentlyContinue |
  Where-Object { $_.Name -notlike 'api-perf-*' -and $_.Name -notlike 'phpunit-*' } |
  Remove-Item -Force -ErrorAction SilentlyContinue
foreach ($db in @($DbCentral, $DbTenant, 'navdhya_test_central', 'navdhya_test_tenant')) {
  if ($db -notlike 'navdhya_*') { throw "refusing to touch database $db" }
}

# ------------------------------------------------- env (real process env) --
$env:DB_CONNECTION = 'central'
$env:CENTRAL_DB_DRIVER = 'mysql'
$env:CENTRAL_DB_HOST = '127.0.0.1'
$env:CENTRAL_DB_PORT = '3306'
$env:CENTRAL_DB_DATABASE = $DbCentral
$env:CENTRAL_DB_USERNAME = $DbUser
$env:CENTRAL_DB_PASSWORD = $DbPass
$env:TENANT_DB_DRIVER = 'mysql'
$env:TENANT_DB_HOST = '127.0.0.1'
$env:TENANT_DB_PORT = '3306'
$env:TENANT_DB_DATABASE = $DbTenant
$env:TENANT_DB_USERNAME = $DbUser
$env:TENANT_DB_PASSWORD = $DbPass
$env:OTP_LOG_CODES = 'true'                       # drill-only code log (config/customers.php)
$env:IDENTITY_HMAC_SECRET = 'e2e-hmac-secret-0123456789abcdef'
$env:CUSTOMER_BACKEND = 'laravel'
$env:NAVDHYA_API_BASE = $LaravelUrl
# next start runs NODE_ENV=production, which would mint
# `__Host-navdhya-session; Secure` over plain HTTP. Contract section 7 assigns
# the no-Secure/no-__Host- policy to HTTP explicitly, so forcing it here
# is the contract-honest config for this run (the prod policy itself is
# pinned by the Jest cookie-policy tests).
$env:SESSION_COOKIE_SECURE = '0'

$failedPhases = @()

try {
  # ------------------------------------------------------ provision DBs --
  Say "resetting e2e databases ($DbCentral, $DbTenant) as root"
  Invoke-Mysql @"
DROP DATABASE IF EXISTS $DbCentral;
DROP DATABASE IF EXISTS $DbTenant;
CREATE DATABASE $DbCentral CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE $DbTenant CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
"@

  # ------------------------------------------------------ migrate:fresh --
  Say 'migrate:fresh on MySQL (scoped navdhya_e2e user, both kernel connections)'
  Push-Location (Join-Path $Root 'platform-navdhya')
  try {
    & php artisan migrate:fresh --force --no-ansi
    if ($LASTEXITCODE -ne 0) { throw "migrate:fresh exited $LASTEXITCODE" }

    # ---------------------------------------------------- seed tenant --
    Say 'seeding active tenant (TenantFactory default state=active)'
    $seedOut = & php artisan tinker --no-ansi --execute `
      "`$t = \App\Platform\Tenancy\Models\Tenant::factory()->create(); echo 'E2ETENANT=' . `$t->getKey();"
    $tenantId = ([regex]::Match(($seedOut -join "`n"), 'E2ETENANT=(\S+)')).Groups[1].Value
    if (-not $tenantId) { throw "tenant seed failed: $seedOut" }
    Say "tenant id = $tenantId"
    $env:E2E_TENANT_ID = $tenantId      # scenarios.mjs (direct Laravel asserts)
    $env:CUSTOMER_TENANT_ID = $tenantId  # BFF laravelTenant() - without this
    # every forwarded payload carries tenant_id:'' and the backend answers
    # 422 validation_failed on all auth routes.

    # ------------------------------------------ fresh drill log surface --
    Get-ChildItem (Join-Path $Root 'platform-navdhya\storage\logs') -Filter 'laravel*.log' -ErrorAction SilentlyContinue |
      ForEach-Object { Remove-Item $_.FullName -Force -ErrorAction SilentlyContinue }
  } finally { Pop-Location }

  # ------------------------------------------------------- build guard --
  $buildId = Join-Path $Root 'apps\web\.next\BUILD_ID'
  if (-not (Test-Path $buildId)) {
    if ($SkipBuild) { throw 'apps/web/.next missing and -SkipBuild given' }
    Say 'apps/web/.next missing - building (NEXT_STANDALONE=0)'
    $env:NEXT_STANDALONE = '0'
    Push-Location $Root
    try {
      & pnpm --filter @navdhya/web run build
      if ($LASTEXITCODE -ne 0) { throw "build exited $LASTEXITCODE" }
    } finally { Pop-Location }
  }

  # ------------------------------------------------------ boot servers --
  Stop-Port 8000; Stop-Port 3000
  Say 'booting Laravel (php artisan serve :8000)'
  Start-Laravel
  if (-not (Wait-Http "$LaravelUrl/up" 90)) { throw 'Laravel did not become ready on :8000' }
  Say 'Laravel ready'

  Say 'booting BFF (next start :3000)'
  Start-Bff
  if (-not (Wait-Http "$BffUrl/" 120)) { throw 'BFF did not become ready on :3000' }
  Say 'BFF ready'

  # ---------------------------------------------------------- warm-up --
  # The first auth POST pays PHP's cold route-compile (~3s observed,
  # over the BFF's 'fast' 2s class -> S1 timed out into an uncaught
  # empty 500 and cascaded into S2/S4). One DIRECT backend signup -
  # bypassing the BFF and its timeout classes - compiles the auth
  # routes while both servers are otherwise idle, immediately before
  # the scenarios. Its single shared-throttle slot is absorbed: the
  # backend window opens here, so it expires ~60s in, before the first
  # thHit() 61s sleep ends (8 paced hits + 1 warm = 9 of 10). The warm
  # phone is distinct from every E2E identity (A/B/C/UI) so no scenario
  # db assert can see its challenge row.
  $warmBody = @{ tenant_id = $env:E2E_TENANT_ID; phone = '+919876500099'; name = 'E2E Warmup' } | ConvertTo-Json -Compress
  try {
    $warm = Invoke-WebRequest -Uri "$LaravelUrl/api/v1/customers/auth/signup" -Method Post -ContentType 'application/json' -Body $warmBody -UseBasicParsing -TimeoutSec 60
    Say "warm-up direct backend signup: $($warm.StatusCode)"
  } catch {
    Say "warm-up direct backend signup failed (continuing): $($_.Exception.Message)"
  }

  # ------------------------------------------------- HTTP scenarios S1-S13 --
  $env:E2E_BFF = $BffUrl
  $env:E2E_LARAVEL = $LaravelUrl
  $env:E2E_MYSQL_EXE = $MysqlExe
  $env:E2E_DB_USER = $DbUser
  $env:E2E_DB_PASS = $DbPass
  $env:E2E_DB_CENTRAL = $DbCentral
  $env:E2E_DB_TENANT = $DbTenant
  $env:E2E_LARAVEL_LOG_DIR = Join-Path $Root 'platform-navdhya\storage\logs'

  Say 'running HTTP scenarios (S1-S13)'
  & node (Join-Path $PSScriptRoot 'scenarios.mjs')
  $httpCode = $LASTEXITCODE
  if ($httpCode -ne 0) { $failedPhases += "HTTP(scenarios exit $httpCode)" }
  Say "HTTP phase exit code: $httpCode"

  # ------------------------------------------------------ Playwright UI --
  if (-not $HttpOnly) {
    Say 'restarting BFF for a fresh in-process rate limiter state'
    Stop-Port 3000
    Start-Bff
    if (-not (Wait-Http "$BffUrl/" 120)) { throw 'BFF did not restart in time' }
    # CACHE_STORE=database: the shared `throttle:10,1` window lives in
    # the DB and survives a Laravel restart, so the HTTP phase's 25
    # backend hits would starve the UI phase (8 hits, budget 10).
    # cache:clear restores phase parity with the BFF restart above;
    # SESSION_DRIVER=database keeps sessions in their own table,
    # untouched by cache:clear.
    Push-Location (Join-Path $Root 'platform-navdhya')
    try {
      & php artisan cache:clear --no-ansi
      if ($LASTEXITCODE -ne 0) { throw "cache:clear exited $LASTEXITCODE" }
    } finally { Pop-Location }

    Say 'Playwright: install (idempotent, standalone e2e package)'
    Push-Location $PSScriptRoot
    try {
      $pwCli = Join-Path $PSScriptRoot 'node_modules\@playwright\test\cli.js'
      if (-not (Test-Path $pwCli)) {
        # e2e/ is NOT a workspace member: a workspace-root install would
        # hijack plain `pnpm install` / `pnpm exec` from this directory.
        & pnpm install --ignore-workspace
        if ($LASTEXITCODE -ne 0) { throw "pnpm install exited $LASTEXITCODE" }
      }
      & node $pwCli install chromium
      if ($LASTEXITCODE -ne 0) { throw "playwright browser install exited $LASTEXITCODE" }

      Say 'Playwright UI flow (signup, 3-attempt exhaustion, login, logout)'
      $env:E2E_LARAVEL_LOG_DIR = Join-Path $Root 'platform-navdhya\storage\logs'
      & node $pwCli test
      $uiCode = $LASTEXITCODE
      if ($uiCode -ne 0) { $failedPhases += "Playwright(exit $uiCode)" }
      Say "Playwright phase exit code: $uiCode"
    } finally { Pop-Location }
  }
} finally {
  Say 'teardown: stopping servers'
  Stop-Port 8000
  Stop-Port 3000
}

Say ('RESULT: ' + ($(if ($failedPhases.Count -eq 0) { 'ALL PHASES PASS' } else { 'FAILURES: ' + ($failedPhases -join ', ') })))
if ($failedPhases.Count -gt 0) {
  Say "post-mortem logs: $LogDir (laravel-out.log = backend statuses, bff*-err.log = Next stacks)"
}
if ($failedPhases.Count -eq 0) { exit 0 } else { exit 1 }
