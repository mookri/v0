param(
  [string]$BaseUrl = "http://localhost:3000"
)

$ErrorActionPreference = "Stop"

$root = Split-Path -Parent $PSScriptRoot
$pagesDir = Join-Path $root "content/pages"
$registryPath = Join-Path $root "content/registry.json"

if (-not (Test-Path $registryPath)) {
  throw "Registry file not found at $registryPath"
}

$registry = Get-Content -Raw $registryPath | ConvertFrom-Json
$clusterMap = @{}
$registryPageMap = @{}

$registry.clusters.PSObject.Properties | ForEach-Object {
  $clusterMap[$_.Name] = $_.Value.basePath
}

$registry.pages.PSObject.Properties | ForEach-Object {
  $registryPageMap[$_.Name] = $_.Value
}

$diskPages = Get-ChildItem -Path $pagesDir -Filter "*.json" | Sort-Object Name | ForEach-Object {
  $page = Get-Content -Raw $_.FullName | ConvertFrom-Json

  if (-not $page.slug -or -not $page.cluster) {
    throw "Invalid page JSON: $($_.FullName)"
  }

  [pscustomobject]@{
    file = $_.Name
    slug = [string]$page.slug
    cluster = [string]$page.cluster
  }
}

$syncIssues = @()

foreach ($page in $diskPages) {
  $entry = $registryPageMap[$page.slug]

  if (-not $entry) {
    $syncIssues += [pscustomobject]@{
      slug = $page.slug
      file = $page.file
      issue = "Missing from registry.json"
    }
    continue
  }

  if ($entry.file -ne $page.file -or $entry.cluster -ne $page.cluster) {
    $syncIssues += [pscustomobject]@{
      slug = $page.slug
      file = $page.file
      issue = "Registry mismatch (expected file=$($page.file), cluster=$($page.cluster); got file=$($entry.file), cluster=$($entry.cluster))"
    }
  }
}

foreach ($slug in $registryPageMap.Keys) {
  if (-not ($diskPages | Where-Object { $_.slug -eq $slug })) {
    $syncIssues += [pscustomobject]@{
      slug = $slug
      file = [string]$registryPageMap[$slug].file
      issue = "Present in registry.json but missing from content/pages"
    }
  }
}

if ($syncIssues.Count -gt 0) {
  $syncIssues | Format-Table -AutoSize
  throw "Registry and content/pages are out of sync."
}

$results = foreach ($page in $diskPages) {
  $basePath = $clusterMap[$page.cluster]
  if (-not $basePath) {
    throw "Unknown cluster '$($page.cluster)' for slug '$($page.slug)'"
  }
  $path = "/$basePath/$($page.slug)"

  try {
    $response = Invoke-WebRequest -Uri ($BaseUrl + $path) -UseBasicParsing
    [pscustomobject]@{
      path = $path
      status = [int]$response.StatusCode
      ok = $response.Content -notmatch "Page Not Found"
    }
  } catch {
    [pscustomobject]@{
      path = $path
      status = if ($_.Exception.Response) { [int]$_.Exception.Response.StatusCode.value__ } else { -1 }
      ok = $false
    }
  }
}

$failed = @($results | Where-Object { -not $_.ok -or $_.status -ne 200 })

if ($failed.Count -gt 0) {
  $failed | Format-Table -AutoSize
  throw "SEO route audit failed for $($failed.Count) route(s)."
}

[pscustomobject]@{
  total = $results.Count
  failed = $failed.Count
  baseUrl = $BaseUrl
} | Format-List
