param(
    [string]$ConfigPath,
    [string]$FcmPath
)

$ErrorActionPreference = 'Stop'

try {
    if (-not (Test-Path $ConfigPath)) { throw "config.json not found at $ConfigPath" }
    $cfg = Get-Content -Raw -Path $ConfigPath | ConvertFrom-Json
    $cfg | Add-Member -Force -MemberType NoteProperty -Name fcm_service_account_path -Value $FcmPath
    $json = $cfg | ConvertTo-Json
    [System.IO.File]::WriteAllText($ConfigPath, $json, (New-Object System.Text.UTF8Encoding($false)))
    Write-Host "config.json FCM path set."
} catch {
    Write-Host "config.json FCM path update failed: $($_.Exception.Message)"
}