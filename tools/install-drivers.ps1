param([string]$XamppDir = 'C:\xampp')

$ErrorActionPreference = 'Stop'
$phpExe = Join-Path $XamppDir 'php\php.exe'
$extDir = Join-Path $XamppDir 'php\ext'
$phpIni = Join-Path $XamppDir 'php\php.ini'
$isAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

try {
    Write-Host '    - Detecting PHP version...'
    $verLine = (& $phpExe -v | Select-String '^PHP (\d+)\.(\d+)' | Select-Object -First 1)
    if (-not $verLine) { throw 'Could not read PHP version.' }
    $maj = $verLine.Matches[0].Groups[1].Value
    $min = $verLine.Matches[0].Groups[2].Value
    $tag = "$maj$min"
    Write-Host "    - PHP $maj.$min (tag $tag)"

    $driverUrl = switch ($tag) {
        { $_ -ge 83 } { 'https://go.microsoft.com/fwlink/?LinkId=2375152' }
        { $_ -ge 81 } { 'https://go.microsoft.com/fwlink/?linkid=2258816' }
        default      { 'https://go.microsoft.com/fwlink/?linkid=2246325' }
    }
    Write-Host "    - Using driver package: $driverUrl"

    $needOdbc = -not (Test-Path 'HKLM:\SOFTWARE\ODBC\ODBCINST.INI\ODBC Driver 18 for SQL Server') -and -not (Test-Path 'HKLM:\SOFTWARE\ODBC\ODBCINST.INI\ODBC Driver 17 for SQL Server')
    if ($needOdbc) {
        if ($isAdmin) {
            Write-Host '    - Installing Microsoft ODBC Driver 18 for SQL Server...'
            $msi = Join-Path $env:TEMP 'msodbcsql18.msi'
            Invoke-WebRequest -UseBasicParsing 'https://go.microsoft.com/fwlink/?linkid=2378279' -OutFile $msi
            $p = Start-Process msiexec -ArgumentList "/i `"$msi`" /qn IACCEPTMSODBCSQLLICENSETERMS=YES" -Wait -PassThru
            if ($p.ExitCode -ne 0) {
                Write-Host '    [WARN] ODBC install failed. Install it manually from https://learn.microsoft.com/sql/connect/odbc/download-odbc-driver-for-sql-server'
            }
        } else {
            Write-Host '    [WARN] Microsoft ODBC Driver 17/18 for SQL Server is NOT installed.'
            Write-Host '      PHP cannot connect to SQL Server until it is installed (needs admin, one time).'
            Write-Host '      Ask an administrator to install it, then restart Apache:'
            Write-Host '      https://learn.microsoft.com/sql/connect/odbc/download-odbc-driver-for-sql-server'
        }
    } else {
        Write-Host '    - ODBC driver already present.'
    }

    Write-Host '    - Downloading PHP SQL Server drivers...'
    $zip = Join-Path $env:TEMP 'sqlsrv.zip'
    Invoke-WebRequest -UseBasicParsing $driverUrl -OutFile $zip
    $unz = Join-Path $env:TEMP 'sqlsrv-extract'
    if (Test-Path $unz) { Remove-Item $unz -Recurse -Force }
    Expand-Archive -Force $zip -DestinationPath $unz

    $installed = @()
    foreach ($base in 'php_sqlsrv', 'php_pdo_sqlsrv') {
        $dll = Get-ChildItem $unz -Recurse -Filter "${base}_${tag}_ts_x64*.dll" | Select-Object -First 1
        if (-not $dll) { $dll = Get-ChildItem $unz -Recurse -Filter "*.dll" | Where-Object { $_.Name -like "*${base}*${tag}*ts*x64*" } | Select-Object -First 1 }
        if (-not $dll) { Write-Host "    [WARN] Could not find driver DLL for $base ($tag)."; continue }
        try {
            Copy-Item $dll.FullName (Join-Path $extDir $dll.Name) -Force
            Write-Host "    - $($dll.Name) installed."
            $installed += $dll.Name
        } catch {
            Write-Host "    [WARN] Could not copy $($dll.Name) - permission denied or file in use."
            Write-Host '      Right-click the installer and choose "Run as administrator", or stop Apache first.'
        }
    }

    Write-Host '    - Enabling extensions in php.ini...'
    if (-not (Test-Path $phpIni)) { throw "php.ini not found at $phpIni" }
    $ini = Get-Content $phpIni
    foreach ($dllName in $installed) {
        $pat = "extension=$dllName"
        $present = $ini | Where-Object { $_ -eq $pat }
        if (-not $present) {
            $commented = $ini | Where-Object { $_ -eq ";$pat" }
            if ($commented) {
                $ini = $ini | ForEach-Object { if ($_ -eq ";$pat") { $pat } else { $_ } }
            } else {
                $ini += $pat
            }
            Write-Host "    - php.ini: added $pat"
        } else {
            Write-Host "    - php.ini: $pat already enabled"
        }
    }
    try {
        Set-Content -Path $phpIni -Value $ini -Encoding ASCII
        Write-Host '    - php.ini updated.'
    } catch {
        Write-Host '    [WARN] Could not save php.ini - permission denied. Run the installer as administrator.'
    }

    Write-Host '    - Verifying...'
    $mods = & $phpExe -m
    if ($mods -match 'pdo_sqlsrv') {
        Write-Host '    OK - pdo_sqlsrv is now loaded.'
        exit 0
    } else {
        if ($needOdbc -and -not $isAdmin) {
            Write-Host '    [INFO] Driver files installed, but it will only work after the ODBC driver above is installed (needs admin).'
            exit 2
        } else {
            Write-Host '    [WARN] pdo_sqlsrv still not loaded. Restart Apache (XAMPP Control Panel) or check php.ini.'
            exit 1
        }
    }
} catch {
    Write-Host "    [ERROR] Driver install failed: $($_.Exception.Message)"
    Write-Host '    Install manually: https://learn.microsoft.com/sql/connect/php/download-drivers-php-sql-server'
    exit 1
}