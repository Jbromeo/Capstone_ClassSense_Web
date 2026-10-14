param(
    [string]$ConfigPath,
    [string]$DbHost = 'localhost',
    [string]$DbUser = 'sa',
    [string]$DbPass = ''
)

$ErrorActionPreference = 'Stop'

try {
    if (Test-Path $ConfigPath) {
        $cfg = Get-Content -Raw -Path $ConfigPath | ConvertFrom-Json
    } else {
        $cfg = New-Object PSObject
    }
    $cfg | Add-Member -Force -MemberType NoteProperty -Name db_host -Value $DbHost
    $cfg | Add-Member -Force -MemberType NoteProperty -Name db_user -Value $DbUser
    $cfg | Add-Member -Force -MemberType NoteProperty -Name db_pass -Value $DbPass
    $json = $cfg | ConvertTo-Json
    [System.IO.File]::WriteAllText($ConfigPath, $json, (New-Object System.Text.UTF8Encoding($false)))
    Write-Host "config.json updated: host=$DbHost user=$DbUser"
} catch {
    Write-Host "config.json update failed: $($_.Exception.Message)"
}