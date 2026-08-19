Add-Type -AssemblyName System.IO.Compression.FileSystem
Add-Type -AssemblyName System.IO.Compression

$sourceDir = "C:\Users\littl\Desktop\flavor-like-master"
$zipPath = "C:\Users\littl\Desktop\flavor-like.zip"
$prefix = "flavor-like"

if (Test-Path $zipPath) { Remove-Item $zipPath -Force }

$zip = [System.IO.Compression.ZipFile]::Open($zipPath, 'Create')

$files = Get-ChildItem -Path $sourceDir -Recurse -File | Where-Object { $_.FullName -notlike "*\.git\*" -and $_.Name -ne "build-zip.ps1" }

foreach ($file in $files) {
    $relativePath = $file.FullName.Substring($sourceDir.Length + 1)
    $entryName = "$prefix/" + $relativePath.Replace('\', '/')
    [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $file.FullName, $entryName, [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null
}

$zip.Dispose()
Write-Host "Done: $zipPath"
