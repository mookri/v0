# e2e/smoke.ps1 - API-surface smoke orchestration.
#
# Boots both servers against the EXISTING navdhya_e2e_* databases (no
# reprovision, no Playwright, no build) and runs one probe script:
#
#   powershell -ExecutionPolicy Bypass -File e2e\smoke.ps1             # api-smoke.mjs
#   powershell -ExecutionPolicy Bypass -File e2e\smoke.ps1 -Script api-smoke2.mjs
#
# Complements run.ps1: run.ps1 = full provision + auth scenarios S1-S13 +
# UI flow; smoke.ps1 = fast non-destructive probe of the routes S1-S13
# does not cover (settings, saved, notifications, ads, revalidate,
# revoke-others, verifications confirm, mfa recovery-codes).
#
# Prerequisites: .next built, navdhya_e2e_* provisioned (run run.ps1
# once), ports 8000/3000 free. Findings: e2e/api-smoke-report.md.
#
# Exit code: 0 iff every assertion passed.
param(
  [string]$Script = 'api-smoke.mjs',
  [string]$TenantId = '',
  [string]$DbCentral = 'navdhya_e2e_central',
  [string]$DbTenant = 'navdhya_e2e_tenant'
)

$ErrorActionPreference = 'Stop'
# next.config.ts re-evaluates `output` at RUNTIME - same reason as run.ps1.
$env:NEXT_STANDALONE = '0'
$Root = Split-Path $PSScriptRoot -Parent
$MysqlExe = 'C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe'
$RootPass = '123456789'
$DbUser = 'navdhya_e2e'
$DbPass = 'navdhya_e2e_2026'
$LaravelUrl = 'http://127.0.0.1:8000'
$BffUrl = 'http://127.0.0.1:3000'
$LogDir = Join-Path $PSScriptRoot '.logs'

function Say([string]$m) { Write-Output "[$(Get-Date -Format HH:mm:ss)] $m" }

foreach ($db in @($DbCentral, $DbTenant, 'navdhya_test_central', 'navdhya_test_tenant')) {
  if ($db -notlike 'navdhya_*') { throw "refusing to touch database $db" }
}
foreach ($f in @('api-smoke.mjs', 'api-smoke2.mjs', 'api-perf.mjs')) {
  if ($Script -eq $f) { $scriptOk = $true }
}
if (-not $scriptOk) { throw "unknown -Script '$Script' (api-smoke.mjs | api-smoke2.mjs | api-perf.mjs)" }

$buildId = Join-Path $Root 'apps\web\.next\BUILD_ID'
if (-not (Test-Path $buildId)) { throw "apps/web/.next missing - build first (see e2e/run.ps1)" }
if (-not (Test-Path (Join-Path $PSScriptRoot $Script))) { throw "script not found: $Script" }

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
$env:OTP_LOG_CODES = 'true'                       # drill-only code log
$env:IDENTITY_HMAC_SECRET = 'e2e-hmac-secret-0123456789abcdef'
$env:CUSTOMER_BACKEND = 'laravel'
$env:NAVDHYA_API_BASE = $LaravelUrl
$env:SESSION_COOKIE_SECURE = '0'
$env:REVALIDATION_SECRET = 'e2e-reval-secret-0123456789'
# Perf instrumentation (drill-only): Laravel [perf] lines + BFF [upstream]
# lines. Log-only — no behaviour change; unset it to silence both.
$env:PERF_TIMING = '1'

# ------------------------------------------------- tenant discovery --------
if (-not $TenantId) {
  $prevPwd = $env:MYSQL_PWD
  $env:MYSQL_PWD = $RootPass
  try {
    $TenantId = (& $MysqlExe -uroot -B -N -e "SELECT id FROM $DbCentral.tenants ORDER BY created_at DESC LIMIT 1" |
      Select-Object -First 1)
  } finally { $env:MYSQL_PWD = $prevPwd }
  if (-not $TenantId) { throw "no tenant found in $DbCentral - run e2e\run.ps1 once to provision" }
}
$env:E2E_TENANT_ID = $TenantId
$env:CUSTOMER_TENANT_ID = $TenantId
Say "tenant id = $TenantId"

function Stop-Port([int]$Port) {
  Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue |
    ForEach-Object { Stop-Process -Id $_.OwningProcess -Force -ErrorAction SilentlyContinue }
}

# ---- clear stale servers + rate-limiter state ----------------------------
Stop-Port 8000; Stop-Port 3000
Start-Sleep -Milliseconds 800

Push-Location (Join-Path $Root 'platform-navdhya')
try {
  & php artisan cache:clear --no-ansi
  Say "cache:clear exit=$LASTEXITCODE"
} finally { Pop-Location }

$laravelProc = $null
$bffProc = $null
$smokeExit = 1
try {
  # ---- boot Laravel ------------------------------------------------------
  Say 'booting Laravel (php artisan serve :8000)'
  $laravelProc = Start-Process -FilePath 'php' -ArgumentList @('artisan', 'serve', '--host=127.0.0.1', '--port=8000') `
    -WorkingDirectory (Join-Path $Root 'platform-navdhya') -WindowStyle Hidden -PassThru `
    -RedirectStandardOutput (Join-Path $LogDir 'smoke-laravel-out.log') `
    -RedirectStandardError (Join-Path $LogDir 'smoke-laravel-err.log')

  $ready = $false
  for ($i = 0; $i -lt 60; $i++) {
    try {
      $r = Invoke-WebRequest -Uri "$LaravelUrl/up" -UseBasicParsing -TimeoutSec 3
      if ($r.StatusCode -eq 200) { $ready = $true; break }
    } catch { Start-Sleep -Milliseconds 700 }
  }
  if (-not $ready) { throw 'Laravel did not become ready on :8000' }
  Say 'Laravel ready'

  # ---- boot BFF ---------------------------------------------------------
  $nextBin = Join-Path $Root 'apps\web\node_modules\next\dist\bin\next'
  if (-not (Test-Path $nextBin)) { throw "next binary not found: $nextBin" }
  Say 'booting BFF (next start :3000)'
  $bffProc = Start-Process -FilePath 'node' -ArgumentList @($nextBin, 'start', '-p', '3000') `
    -WorkingDirectory (Join-Path $Root 'apps\web') -WindowStyle Hidden -PassThru `
    -RedirectStandardOutput (Join-Path $LogDir 'smoke-bff-out.log') `
    -RedirectStandardError (Join-Path $LogDir 'smoke-bff-err.log')

  $ready = $false
  for ($i = 0; $i -lt 80; $i++) {
    try {
      $null = Invoke-WebRequest -Uri "$BffUrl/" -UseBasicParsing -TimeoutSec 5
      $ready = $true; break
    } catch {
      if ($_.Exception.Response) { $ready = $true; break }  # any HTTP answer = listening
      Start-Sleep -Milliseconds 700
    }
  }
  if (-not $ready) { throw 'BFF did not become ready on :3000' }
  Say 'BFF ready'

  # ---- smoke ------------------------------------------------------------
  Say "running $Script"
  & node (Join-Path $PSScriptRoot $Script)
  $smokeExit = $LASTEXITCODE
  Say "smoke exit=$smokeExit"
} finally {
  # ---- teardown (always) ------------------------------------------------
  foreach ($proc in @($bffProc, $laravelProc)) {
    if ($proc -and -not $proc.HasExited) {
      Stop-Process -Id $proc.Id -Force -ErrorAction SilentlyContinue
    }
  }
  Stop-Port 3000; Stop-Port 8000
  Say 'teardown done'
}

exit $smokeExit
