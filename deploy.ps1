<#
.SYNOPSIS
    Builds a clean deployment ZIP for JODER_ENRIQUEEE.

.DESCRIPTION
    Packages the project excluding local/secret/build files so it is safe to
    upload to InfinityFree's htdocs. The secret config/config.php is NEVER
    included - create it on the server after upload.

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File .\deploy.ps1
    powershell -ExecutionPolicy Bypass -File .\deploy.ps1 -Output dist\site.zip
#>
param(
    [string]$Output = ""
)

$ErrorActionPreference = 'Stop'

$root = $PSScriptRoot
$distDir = Join-Path $root 'dist'
if (-not (Test-Path -LiteralPath $distDir)) {
    New-Item -ItemType Directory -Path $distDir | Out-Null
}

if ([string]::IsNullOrWhiteSpace($Output)) {
    $stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
    $Output = Join-Path $distDir "joder-$stamp.zip"
} elseif (-not [System.IO.Path]::IsPathRooted($Output)) {
    $Output = Join-Path $root $Output
}

# Relative paths (forward-slash form) that must never ship.
$excluded = @(
    'config/config.php',
    'deploy.ps1',
    'PLAN.txt',
    'favicon.svg',
    'favicon.png',
    'favicon.ico',
    'favicon.gif',
    'favicon.webp',
    'favicon.jpg',
    'favicon.jpeg'
)
# Directory prefixes (forward-slash form) to skip entirely.
$excludedDirs = @('.git/', 'dist/', 'migrations/')

$files = Get-ChildItem -Path $root -Recurse -File -Force | Where-Object {
    $rel = $_.FullName.Substring($root.Length + 1) -replace '\\', '/'

    if ($rel -match '(^|/)\.git/') { return $false }
    foreach ($dir in $excludedDirs) {
        if ($rel.StartsWith($dir)) { return $false }
    }
    if ($excluded -contains $rel) { return $false }
    if ($rel -match '\.(log|zip)$') { return $false }
    return $true
}

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

if (Test-Path -LiteralPath $Output) {
    Remove-Item -LiteralPath $Output -Force
}

$zip = [System.IO.Compression.ZipFile]::Open($Output, [System.IO.Compression.ZipArchiveMode]::Create)
try {
    foreach ($file in $files) {
        $rel = $file.FullName.Substring($root.Length + 1) -replace '\\', '/'
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $file.FullName, $rel) | Out-Null
    }
} finally {
    $zip.Dispose()
}

Write-Host "Created: $Output" -ForegroundColor Green
Write-Host ("Files packaged: {0}" -f $files.Count)
Write-Host "Remember: upload to htdocs, then open the site and run the installer (setup.php)."
