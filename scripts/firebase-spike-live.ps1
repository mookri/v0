<#
.SYNOPSIS
    Firebase phone-OTP live spike -- Verification Fabric S49 (L1-L6).

.DESCRIPTION
    Runs the live-validation checklist from
    docs/04-security/firebase-phone-otp-spike.md against a real Firebase
    project using its test phone numbers, and emits a REDACTED markdown
    transcript ready to paste into the spike doc.

    Probes:
      L0  recaptchaParams (site key discovery)
      L1  send test number WITHOUT attestation -> accepted or captcha error
      L2  send WITH reCAPTCHA v2 token (skipped unless FIREBASE_RECAPTCHA_TOKEN set)
      L3  verify wrong code (x5 / until message changes), garbage sessionInfo
      L4  happy path: send -> fixed test code -> phoneNumber match
      L5/L6  manual markers (Playwright decision; API-key/App Check posture)

    REDACTION (hard rules): API key never printed; phone numbers masked;
    idToken/refreshToken stripped; sessionInfo replaced by len+sha256;
    OTP codes never printed; any phone-like pattern inside Google error
    messages is masked.

    ASCII-only on purpose: Windows PowerShell 5.1 reads BOM-less scripts
    as ANSI, so non-ASCII characters corrupt parsing.

.EXAMPLE
    # credentials from platform-navdhya\.env (keys: FIREBASE_CUSTOMER_*)
    .\firebase-spike-live.ps1 -OutFile transcript.md

.EXAMPLE
    # pipeline self-test with a deliberately invalid key (no creds needed)
    .\firebase-spike-live.ps1 -SelfTest -OutFile selftest.md
#>
[CmdletBinding()]
param(
    [ValidateSet('customer', 'vendor')]
    [string]$Audience = 'customer',

    [string]$EnvFile,

    [string]$OutFile = '.\firebase-spike-transcript.md',

    # Exercise HTTP + capture + redaction with an invalid key: every probe
    # must come back API_KEY_INVALID / network error. Never real evidence.
    [switch]$SelfTest
)

$ErrorActionPreference = 'Stop'
try {
    [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
} catch {}

# ---------------------------------------------------------------- env ----

function Read-EnvFile([string]$Path) {
    $map = @{}
    if ($Path -and (Test-Path $Path)) {
        Get-Content -Path $Path | ForEach-Object {
            $line = $_.Trim()
            if ($line -match '^[A-Za-z_][A-Za-z0-9_]*\s*=\s*') {
                $i = $line.IndexOf('=')
                $key = $line.Substring(0, $i).Trim()
                $val = $line.Substring($i + 1).Trim()
                if (($val.StartsWith('"') -and $val.EndsWith('"')) -or ($val.StartsWith("'") -and $val.EndsWith("'"))) {
                    $val = $val.Substring(1, $val.Length - 2)
                }
                $map[$key] = $val
            }
        }
    }
    return $map
}

if (-not $EnvFile) {
    $candidate = Join-Path (Split-Path -Parent $PSScriptRoot) (Join-Path 'platform-navdhya' '.env')
    if (-not (Test-Path $candidate)) { $candidate = Join-Path (Get-Location) 'platform-navdhya\.env' }
    if (-not (Test-Path $candidate)) { $candidate = Join-Path (Get-Location) '.env' }
    $EnvFile = $candidate
}
$fileEnv = Read-EnvFile $EnvFile

function Get-EnvValue([string]$Name) {
    $v = [Environment]::GetEnvironmentVariable($Name)
    if (-not $v -and $fileEnv.ContainsKey($Name)) { $v = $fileEnv[$Name] }
    return $v
}

$prefix = $Audience.ToUpper()
$project = Get-EnvValue "FIREBASE_${prefix}_PROJECT"
$apiKey  = Get-EnvValue "FIREBASE_${prefix}_API_KEY"
$siteKey = Get-EnvValue "FIREBASE_${prefix}_RECAPTCHA_KEY"
$captchaToken = Get-EnvValue 'FIREBASE_RECAPTCHA_TOKEN'   # optional, for L2
$testPhone = Get-EnvValue 'FIREBASE_TEST_PHONE'
$testCode  = Get-EnvValue 'FIREBASE_TEST_CODE'
if (-not $testCode) { $testCode = '123456' }

if ($SelfTest) {
    $apiKey = 'AIzaSyFAKE00000000000000000000000000000'
    if (-not $testPhone) { $testPhone = '+910000000000' }
    if (-not $project) { $project = 'selftest-project' }
}

$missing = @()
if (-not $apiKey)     { $missing += "FIREBASE_${prefix}_API_KEY" }
if (-not $testPhone)  { $missing += 'FIREBASE_TEST_PHONE' }
if ($missing.Count -gt 0) {
    Write-Host 'MISSING CREDENTIALS -- the live spike cannot run.' -ForegroundColor Red
    $missing | ForEach-Object { Write-Host ('  - ' + $_) }
    Write-Host ''
    Write-Host 'Provide them in platform-navdhya\.env (gitignored):'
    Write-Host "  FIREBASE_$($prefix)_PROJECT=<project-id>"
    Write-Host "  FIREBASE_$($prefix)_API_KEY=<web-api-key>"
    Write-Host '  FIREBASE_TEST_PHONE=<console test number>'
    Write-Host '  FIREBASE_TEST_CODE=<fixed test code>        # optional, default 123456'
    Write-Host '  FIREBASE_RECAPTCHA_TOKEN=<v2 token>         # optional, enables L2'
    exit 2
}

# ------------------------------------------------------------ redaction ---

function Get-PhoneMask([string]$Phone) {
    if ($Phone -match '^\+(\d{1,3})(\d{3,})(\d{2})$') {
        return '+' + $Matches[1] + ('*' * $Matches[2].Length) + $Matches[3]
    }
    return '<phone:unmasked-shape>'
}

function Protect-Text([string]$Text) {
    if (-not $Text) { return '' }
    return ($Text -replace '\+\d[\d\s\-\.]{6,14}\d', '<phone>')
}

function Get-SessionDigest([string]$Session) {
    if (-not $Session) { return '<none>' }
    $sha = [System.Security.Cryptography.SHA256]::Create()
    try {
        $hash = $sha.ComputeHash([Text.Encoding]::UTF8.GetBytes($Session))
    } finally { $sha.Dispose() }
    $hex = ([BitConverter]::ToString($hash) -replace '-', '').ToLower()
    return '<session len=' + $Session.Length + ' sha256=' + $hex.Substring(0, 12) + '>'
}

# Parse a response body to a hashtable; strip credentials outright.
function Get-RedactedObject([string]$Body) {
    if (-not $Body) { return $null }
    try { $o = $Body | ConvertFrom-Json } catch { return @{ _raw = Protect-Text $Body } }
    $walk = {
        param($node)
        if ($node -is [System.Collections.IEnumerable] -and $node -isnot [string]) {
            return @($node | ForEach-Object { & $walk $_ })
        }
        $h = @{}
        foreach ($p in $node.PSObject.Properties) {
            switch ($p.Name) {
                'idToken'        { $h[$p.Name] = '<stripped>' }
                'refreshToken'   { $h[$p.Name] = '<stripped>' }
                'sessionInfo'    { $h[$p.Name] = Get-SessionDigest ([string]$p.Value) }
                'phoneNumber'    { $h[$p.Name] = Get-PhoneMask ([string]$p.Value) }
                'localId'        { $h[$p.Name] = '<uid len=' + ([string]$p.Value).Length + '>' }
                default {
                    if ($p.Value -is [string]) { $h[$p.Name] = Protect-Text ([string]$p.Value) }
                    elseif ($null -eq $p.Value) { $h[$p.Name] = $null }
                    elseif ($p.Value -is [int] -or $p.Value -is [long] -or $p.Value -is [bool] -or $p.Value -is [double]) { $h[$p.Name] = $p.Value }
                    else { $h[$p.Name] = & $walk $p.Value }
                }
            }
        }
        return $h
    }
    return (& $walk $o)
}

function Format-Redacted([pscustomobject]$Result) {
    $obj = Get-RedactedObject $Result.Body
    $json = if ($null -eq $obj) { '{}' } else { ($obj | ConvertTo-Json -Compress -Depth 8) }
    return 'HTTP ' + $Result.Status + '  ' + (Protect-Text $json)
}

# ------------------------------------------------------------- transport --

function Invoke-Api([string]$Method, [string]$Uri, $Body) {
    $json = $null
    if ($null -ne $Body) { $json = $Body | ConvertTo-Json -Compress }
    try {
        $r = Invoke-WebRequest -Uri $Uri -Method $Method -Body $json `
            -ContentType 'application/json' -UseBasicParsing -TimeoutSec 20
        return [pscustomobject]@{ Status = [int]$r.StatusCode; Body = [string]$r.Content }
    } catch {
        $resp = $_.Exception.Response
        $status = $null; $body = ''
        if ($null -ne $resp) {
            try { $status = [int]$resp.StatusCode } catch {}
            try {
                $stream = $resp.GetResponseStream()
                if ($null -ne $stream) {
                    $stream.Position = 0
                    $reader = New-Object System.IO.StreamReader($stream)
                    $body = $reader.ReadToEnd()
                    $reader.Close()
                }
            } catch {}
        }
        if (-not $body -and $_.ErrorDetails -and $_.ErrorDetails.Message) {
            $body = $_.ErrorDetails.Message
        }
        if (-not $status) { $status = 'network-error' }
        return [pscustomobject]@{ Status = $status; Body = $body }
    }
}

function Get-ErrorCode([pscustomobject]$Result) {
    if ($Result.Status -eq 'network-error') { return 'network_error' }
    try { $o = $Result.Body | ConvertFrom-Json } catch { return 'unparsable' }
    if ($null -eq $o.error) { return 'ok' }

    # Mirror the adapter's symbolicCodes() exactly: message head before
    # the first ':', error.status, error.errors[*].reason -- the parts
    # CODE_ERROR_CODES is calibrated against -- then mask any phone.
    $parts = @()
    $msg = [string]$o.error.message
    if ($msg -ne '') {
        $head = ((($msg -split ':')[0]).Trim()).ToUpperInvariant()
        if ($head -ne '') { $parts += $head }
    }
    $status = [string]$o.error.status
    if ($status -ne '') { $parts += $status.ToUpperInvariant() }
    foreach ($entry in @($o.error.errors)) {
        if ($null -eq $entry) { continue }
        $reason = [string]$entry.reason
        if ($reason -ne '') { $parts += $reason.ToUpperInvariant() }
    }
    if ($parts.Count -eq 0) { $parts += 'error' }
    return (Protect-Text ($parts -join ', '))
}

function Send-Code([string]$Phone, [string]$Token) {
    $body = @{ phoneNumber = $Phone }
    if ($Token) { $body['recaptchaToken'] = $Token }
    return Invoke-Api 'POST' ("https://identitytoolkit.googleapis.com/v1/accounts:sendVerificationCode?key=$apiKey") $body
}

function Test-Session([string]$Session, [string]$Code) {
    return Invoke-Api 'POST' ("https://identitytoolkit.googleapis.com/v1/accounts:signInWithPhoneNumber?key=$apiKey") @{
        sessionInfo = $Session
        code        = $Code
    }
}

# ------------------------------------------------------------- transcript --

$stamp = (Get-Date).ToUniversalTime().ToString('yyyy-MM-dd HH:mm:ss') + ' UTC'
$lines = New-Object System.Collections.Generic.List[string]
$summary = New-Object System.Collections.Generic.List[object]

function Add-Line([string]$Text) { $lines.Add($Text) }
function Add-Probe([string]$Id, [string]$Outcome) {
    $summary.Add([pscustomobject]@{ Probe = $Id; Outcome = $Outcome })
}

$maskPhone = Get-PhoneMask $testPhone

Add-Line '# Firebase phone-OTP live spike -- L1..L6 transcript'
Add-Line ''
Add-Line ('- **When:** ' + $stamp)
Add-Line ('- **Audience:** ' + $audience + ' (project: `' + $project + '`)')
Add-Line ('- **Test number:** `' + $maskPhone + '` (console *Phone numbers for testing*; no SMS, no SMS cost)')
$siteKeyShown = if ($siteKey) { $siteKey } else { '<not set>' }
Add-Line ('- **API key:** `<redacted>`  -  **reCAPTCHA site key:** `' + $siteKeyShown + '`')
if ($SelfTest) { Add-Line '- **SELF-TEST RUN** -- invalid key on purpose; outcomes are pipeline checks, NOT live evidence.' }
Add-Line ''

# L0 -- site key discovery
Add-Line '## L0 -- recaptchaParams (site key discovery)'
$l0 = Invoke-Api 'GET' ("https://identitytoolkit.googleapis.com/v1/recaptchaParams?key=$apiKey")
Add-Line '```'
Add-Line ('GET /v1/recaptchaParams  ->  ' + (Format-Redacted $l0))
Add-Line '```'
$l0code = Get-ErrorCode $l0
Add-Line ''
Add-Probe 'L0' $l0code

# L1 -- send WITHOUT attestation
Add-Line '## L1 -- send test number WITHOUT attestation'
Add-Line '```'
$l1 = Send-Code $testPhone $null
Add-Line ('POST accounts:sendVerificationCode {phoneNumber:' + $maskPhone + '}  ->  ' + (Format-Redacted $l1))
Add-Line '```'
$l1code = Get-ErrorCode $l1
$l1ok = ($l1.Status -eq 200)
Add-Line ''
if ($l1ok) {
    Add-Line '**Result:** test number accepted WITHOUT a reCAPTCHA token (captcha demand can stay off for test numbers).'
} else {
    Add-Line ('**Result:** rejected -- `' + $l1code + '`')
}
Add-Probe 'L1' $l1code
Add-Line ''

# L2 -- send WITH token (optional)
Add-Line '## L2 -- send WITH reCAPTCHA v2 token'
if (-not $captchaToken) {
    Add-Line '**SKIPPED** -- no `FIREBASE_RECAPTCHA_TOKEN` in env (headless minting is the open question; L5 owns the CI story).'
    Add-Probe 'L2' 'skipped:no-token'
} else {
    Add-Line '```'
    $l2 = Send-Code $testPhone $captchaToken
    Add-Line ('POST accounts:sendVerificationCode {phoneNumber:' + $maskPhone + ', recaptchaToken:<v2>}  ->  ' + (Format-Redacted $l2))
    Add-Line '```'
    Add-Probe 'L2' (Get-ErrorCode $l2)
}
Add-Line ''

# L3 -- verify taxonomy: wrong code (repeat until message changes), garbage session
Add-Line '## L3 -- verify taxonomy (wrong code x5, exhausted, garbage session)'
Add-Line '```'
$l3send = Send-Code $testPhone $null
$session = $null
try { $session = ($l3send.Body | ConvertFrom-Json).sessionInfo } catch {}
if (-not $session) {
    Add-Line ('fresh send for L3 failed  ->  ' + (Format-Redacted $l3send))
    Add-Probe 'L3' ('no-session:' + (Get-ErrorCode $l3send))
} else {
    $wrong = '000000'
    if ($wrong -eq $testCode) { $wrong = '111111' }
    $prev = $null
    for ($i = 1; $i -le 5; $i++) {
        $r = Test-Session $session $wrong
        $code = Get-ErrorCode $r
        Add-Line ('verify attempt ' + $i + ' (wrong code)  ->  HTTP ' + $r.Status + '  ' + $code)
        Add-Probe ('L3-wrong#' + $i) $code
        if ($prev -and $code -ne $prev) { Add-Line '  ** message changed -- exhaustion behavior observed above.'; break }
        $prev = $code
    }
    $garbage = Test-Session 'PROBE_NOT_A_REAL_SESSION_INFO' $testCode
    Add-Line ('verify with garbage sessionInfo  ->  HTTP ' + $garbage.Status + '  ' + (Get-ErrorCode $garbage))
    Add-Probe 'L3-garbage-session' (Get-ErrorCode $garbage)
}
Add-Line '```'
Add-Line ''

# L4 -- happy path
Add-Line '## L4 -- happy path round trip'
Add-Line '```'
$l4send = Send-Code $testPhone $null
$session4 = $null
try { $session4 = ($l4send.Body | ConvertFrom-Json).sessionInfo } catch {}
if (-not $session4) {
    Add-Line ('send failed  ->  ' + (Format-Redacted $l4send))
    Add-Probe 'L4' ('send-failed:' + (Get-ErrorCode $l4send))
    $resultLine = '**Result:** send failed.'
} else {
    Add-Line ('send  ->  ' + (Format-Redacted $l4send))
    $l4verify = Test-Session $session4 $testCode
    Add-Line ('verify with fixed test code  ->  ' + (Format-Redacted $l4verify))
    $ok = ($l4verify.Status -eq 200)
    $match = $false
    if ($ok) {
        try {
            $respPhone = ($l4verify.Body | ConvertFrom-Json).phoneNumber
            $match = ($respPhone -eq $testPhone)
        } catch {}
    }
    if ($ok -and $match) { $outcome = 'ok:phoneNumber-match' }
    elseif ($ok) { $outcome = 'ok:phoneNumber-MISMATCH' }
    else { $outcome = Get-ErrorCode $l4verify }
    Add-Probe 'L4' $outcome
    if ($ok -and $match) {
        $resultLine = '**Result:** round trip OK, `phoneNumber` matches the test number (idToken stripped from transcript).'
    } elseif ($ok) {
        $resultLine = '**Result:** verify OK but phoneNumber did NOT match.'
    } else {
        $resultLine = '**Result:** verify FAILED.'
    }
}
Add-Line '```'
Add-Line ''
Add-Line $resultLine
Add-Line ''

# L5 -- manual decision point
Add-Line '## L5 -- Playwright / invisible reCAPTCHA v2 in CI (DECISION POINT)'
$tokenState = if ($captchaToken) { 'provided' } else { 'not provided (skipped)' }
Add-Line ('- L2 outcome above feeds this: token availability = `' + $tokenState + '`.')
Add-Line ('- Manual step: run the customer UI flow with `OTP_PROVIDER_' + $prefix + '=firebase` and observe whether the invisible reCAPTCHA v2 widget completes in headless CI, or the UI flow must stay on `LocalCodeProvider`.')
Add-Line ''

# L6 -- manual posture checklist
Add-Line '## L6 -- API-key restrictions + App Check posture (MANUAL, per project)'
Add-Line '- Console > APIs & Services > Credentials > Web key: application restrictions (HTTP referrer / IP) + API restrictions (Identity Toolkit only).'
Add-Line '- Confirm the key used here is restricted the same way (an unrestricted key in prod is a finding).'
Add-Line '- App Check posture recorded per project (not applicable to pure server-side REST calls).'
Add-Line ''

# Summary / taxonomy calibration table
Add-Line '## Probe summary (feeds CODE_ERROR_CODES calibration)'
Add-Line ''
Add-Line '| Probe | Outcome |'
Add-Line '|---|---|'
foreach ($s in $summary) { Add-Line ('| ' + $s.Probe + ' | `' + $s.Outcome + '` |') }

$text = ($lines -join "`n") + "`n"
$outPath = $OutFile
if (-not [System.IO.Path]::IsPathRooted($outPath)) { $outPath = Join-Path (Get-Location) $outPath }
[System.IO.File]::WriteAllText($outPath, $text)   # UTF-8, no BOM
Write-Host ('transcript -> ' + $outPath)
Write-Host ''
Write-Host (($text -split "`n" | Select-Object -First 40) -join "`n")
exit 0
