Add-Type -AssemblyName System.IO.Compression.FileSystem
Add-Type -AssemblyName System.IO.Compression

$sourceDir = if ($PSScriptRoot) { $PSScriptRoot } else { (Get-Location).Path }

Write-Host "Compiling minified assets via build-assets.js..."
node (Join-Path $sourceDir "build-assets.js")

$zipPath = Join-Path (Split-Path $sourceDir -Parent) "flavor-like.zip"
$prefix = "flavor-like"

if (Test-Path $zipPath) { Remove-Item $zipPath -Force }

$zip = [System.IO.Compression.ZipFile]::Open($zipPath, 'Create')

$files = Get-ChildItem -Path $sourceDir -Recurse -File | Where-Object { 
    $_.FullName -notlike "*\.git\*" -and 
    $_.FullName -notlike "*\node_modules\*" -and 
    $_.FullName -notlike "*\tests\*" -and 
    $_.Name -ne "build-zip.ps1" -and
    $_.Name -ne "build-assets.js" -and
    $_.Name -ne "test-templates.html" -and
    $_.Name -ne "generate-mo.ps1"
}

foreach ($file in $files) {
    $relativePath = $file.FullName.Substring($sourceDir.Length + 1)
    $entryName = "$prefix/" + $relativePath.Replace('\', '/')
    [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $file.FullName, $entryName, [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null
}

$zip.Dispose()
Write-Host "Done: $zipPath"
