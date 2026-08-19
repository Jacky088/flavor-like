Add-Type -AssemblyName System.Text.Encoding

$poFile = "C:\Users\littl\Desktop\flavor-like-master\languages\flavor-like-zh_CN.po"
$moFile = "C:\Users\littl\Desktop\flavor-like-master\languages\flavor-like-zh_CN.mo"

$poContent = [System.IO.File]::ReadAllText($poFile, [System.Text.Encoding]::UTF8)
$entries = @{}
$currentMsgid = ""
$currentMsgstr = ""
$inMsgid = $false
$inMsgstr = $false

foreach ($line in $poContent -split "`n") {
    $line = $line.Trim()
    
    if ($line -eq "" -or $line.StartsWith("#")) {
        if ($inMsgstr -and $currentMsgid -ne "") {
            $entries[$currentMsgid] = $currentMsgstr
        }
        $inMsgid = $false
        $inMsgstr = $false
        $currentMsgid = ""
        $currentMsgstr = ""
        continue
    }
    
    if ($line.StartsWith('msgid "')) {
        if ($inMsgstr -and $currentMsgid -ne "") {
            $entries[$currentMsgid] = $currentMsgstr
        }
        $inMsgid = $true
        $inMsgstr = $false
        $currentMsgid = $line.Substring(7, $line.Length - 8) -replace '\\n',"`n" -replace '\\t',"`t" -replace '\\"','"'
    }
    elseif ($line.StartsWith('msgstr "')) {
        $inMsgid = $false
        $inMsgstr = $true
        $currentMsgstr = $line.Substring(8, $line.Length - 9) -replace '\\n',"`n" -replace '\\t',"`t" -replace '\\"','"'
    }
    elseif ($line.StartsWith('msgid_plural "') -or $line -match '^msgstr\[\d+\]') {
        # skip plurals
    }
    elseif ($line.StartsWith('"') -and $line.EndsWith('"')) {
        $content = $line.Substring(1, $line.Length - 2) -replace '\\n',"`n" -replace '\\t',"`t" -replace '\\"','"'
        if ($inMsgid) { $currentMsgid += $content }
        elseif ($inMsgstr) { $currentMsgstr += $content }
    }
}
if ($inMsgstr -and $currentMsgid -ne "") {
    $entries[$currentMsgid] = $currentMsgstr
}

# Separate header
$header = if ($entries.ContainsKey("")) { $entries[""] } else { "" }
$entries.Remove("")

# Filter empty translations and sort
$filtered = @{}
foreach ($k in $entries.Keys) {
    if ($entries[$k] -ne "") { $filtered[$k] = $entries[$k] }
}

$sortedKeys = $filtered.Keys | Sort-Object
$allKeys = @("") + $sortedKeys
$allValues = @($header) + ($sortedKeys | ForEach-Object { $filtered[$_] })

$count = $allKeys.Count
$headerSize = 28

# Calculate offsets
$utf8 = [System.Text.Encoding]::UTF8
$origBytes = @()
$transBytes = @()
foreach ($k in $allKeys) { $origBytes += ,($utf8.GetBytes($k)) }
foreach ($v in $allValues) { $transBytes += ,($utf8.GetBytes($v)) }

$dataOffset = $headerSize + $count * 8 * 2
$currentOffset = $dataOffset

$oOffsets = @()
foreach ($b in $origBytes) {
    $oOffsets += ,@($b.Length, $currentOffset)
    $currentOffset += $b.Length + 1
}

$tOffsets = @()
foreach ($b in $transBytes) {
    $tOffsets += ,@($b.Length, $currentOffset)
    $currentOffset += $b.Length + 1
}

# Build MO binary
$ms = New-Object System.IO.MemoryStream
$bw = New-Object System.IO.BinaryWriter($ms)

$bw.Write([byte[]]@(0xde, 0x12, 0x04, 0x95))  # magic number (little-endian)
$bw.Write([uint32]0)           # revision
$bw.Write([uint32]$count)      # number of strings
$bw.Write([uint32]$headerSize) # orig table offset
$bw.Write([uint32]($headerSize + $count * 8)) # trans table offset
$bw.Write([uint32]0)           # hash size
$bw.Write([uint32]0)           # hash offset

foreach ($o in $oOffsets) { $bw.Write([uint32]$o[0]); $bw.Write([uint32]$o[1]) }
foreach ($t in $tOffsets) { $bw.Write([uint32]$t[0]); $bw.Write([uint32]$t[1]) }

foreach ($b in $origBytes) { $bw.Write($b); $bw.Write([byte]0) }
foreach ($b in $transBytes) { $bw.Write($b); $bw.Write([byte]0) }

$bw.Flush()
[System.IO.File]::WriteAllBytes($moFile, $ms.ToArray())
$bw.Close()
$ms.Close()

Write-Host "MO file created: $moFile ($count entries)"
