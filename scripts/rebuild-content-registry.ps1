$ErrorActionPreference = "Stop"

$root = Split-Path -Parent $PSScriptRoot
$pagesDir = Join-Path $root "content/pages"
$registryPath = Join-Path $root "content/registry.json"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

$clusters = [ordered]@{
  brand = [ordered]@{ label = "Inside the Brand"; basePath = "explore" }
  services = [ordered]@{ label = "Puja Services"; basePath = "services" }
  ceremonies = [ordered]@{ label = "Hindu Ceremonies"; basePath = "ceremonies" }
  "ritual-guides" = [ordered]@{ label = "Ritual Guides"; basePath = "ritual-guides" }
  "pandit-services" = [ordered]@{ label = "Pandit Services"; basePath = "pandit-services" }
  "festival-rituals" = [ordered]@{ label = "Festival Rituals"; basePath = "festival-rituals" }
  "spiritual-guides" = [ordered]@{ label = "Spiritual Guides"; basePath = "spiritual-guides" }
  faq = [ordered]@{ label = "Frequently Asked Questions"; basePath = "faq" }
  blog = [ordered]@{ label = "Navdhya Journal"; basePath = "blog" }
}

$pages = [ordered]@{}

Get-ChildItem -Path $pagesDir -Filter "*.json" | Sort-Object Name | ForEach-Object {
  $page = Get-Content -Raw $_.FullName | ConvertFrom-Json
  if (-not $page.slug -or -not $page.cluster) { return }
  $pages[$page.slug] = [ordered]@{
    file = $_.Name
    cluster = $page.cluster
  }
}

$registry = [ordered]@{
  clusters = $clusters
  pages = $pages
}

$json = $registry | ConvertTo-Json -Depth 6
[System.IO.File]::WriteAllText($registryPath, $json, $utf8NoBom)
Write-Output "Registry rebuilt with $($pages.Keys.Count) pages."
