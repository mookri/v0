$root = (Get-Location).Path
$skip = @("node_modules", ".git", ".next")

function Show-Tree {
    param(
        [string]$Path,
        [string]$Prefix = ""
    )

    $items = Get-ChildItem -LiteralPath $Path -Force |
        Where-Object { $skip -notcontains $_.Name } |
        Sort-Object @{Expression={$_.PSIsContainer};Descending=$true}, Name

    for ($i = 0; $i -lt $items.Count; $i++) {
        $item = $items[$i]
        $last = ($i -eq $items.Count - 1)

        if ($last) {
            $branch = "└── "
            $nextPrefix = "$Prefix    "
        } else {
            $branch = "├── "
            $nextPrefix = "$Prefix│   "
        }

        "$Prefix$branch$($item.Name)"

        if ($item.PSIsContainer) {
            Show-Tree -Path $item.FullName -Prefix $nextPrefix
        }
    }
}

@(
    (Split-Path $root -Leaf)
    (Show-Tree -Path $root)
) | Set-Content -Path "project-tree.txt" -Encoding UTF8