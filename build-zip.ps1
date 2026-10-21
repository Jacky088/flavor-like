Add-Type -AssemblyName System.IO.Compression.FileSystem
Add-Type -AssemblyName System.IO.Compression

$sourceDir = if ($PSScriptRoot) { $PSScriptRoot } else { (Get-Location).Path }

Write-Host "Building assets and Gutenberg blocks via npm..."
Push-Location $sourceDir
npm run build:assets
if ($LASTEXITCODE -ne 0) { Pop-Location; throw "Asset build failed." }
npm run build:blocks
if ($LASTEXITCODE -ne 0) { Pop-Location; throw "Block build failed." }
Pop-Location

$versionMatch = Select-String -Path (Join-Path $sourceDir 'flavor-like.php') -Pattern '^\s*\*\s*Version:\s*(\S+)' | Select-Object -First 1
$version = if ($versionMatch) { $versionMatch.Matches[0].Groups[1].Value } else { 'dev' }

$distDir = Join-Path $sourceDir 'dist'
if (-not (Test-Path $distDir)) { New-Item -ItemType Directory -Path $distDir | Out-Null }

$zipPath = Join-Path $distDir "flavor-like-$version.zip"
$prefix = "flavor-like"

if (Test-Path $zipPath) { Remove-Item $zipPath -Force }

$zip = [System.IO.Compression.ZipFile]::Open($zipPath, 'Create')

# Directories and files that must not ship in the plugin package.
$excludePaths = @(
    "\.git\",
    "\node_modules\",
    "\tests\",
    "\wp-assets\",
    "\assets\sass\",
    "\admin\assets\sass\",
    "\assets\js\src\",
    "\includes\blocks\button\src\",
    "\includes\blocks\top-content\src\",
    "\.vscode\",
    "\dist\"
)
$excludeNames = @(
    "build-zip.ps1",
    "generate-mo.ps1",
    "package.json",
    "package-lock.json",
    "wp-toolkit.config.mjs",
    "webpack.config.js",
    ".gitignore",
    ".DS_Store"
)

$files = Get-ChildItem -Path $sourceDir -Recurse -File | Where-Object {
    $relativePath = $_.FullName.Substring($sourceDir.Length)
    $excluded = $relativePath -like "\build\*"
    foreach ($pattern in $excludePaths) {
        if ($relativePath -like "*$pattern*") { $excluded = $true; break }
    }
    if (-not $excluded -and $excludeNames -contains $_.Name) { $excluded = $true }
    -not $excluded
}

foreach ($file in $files) {
    $relativePath = $file.FullName.Substring($sourceDir.Length + 1)
    $entryName = "$prefix/" + $relativePath.Replace('\', '/')
    [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $file.FullName, $entryName, [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null
}

$zip.Dispose()
Write-Host "Done: $zipPath"
