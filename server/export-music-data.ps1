$ErrorActionPreference = "Stop"

function Get-EnvMap {
    param([string]$Path)
    $map = @{}
    if (-not (Test-Path $Path)) { return $map }
    Get-Content $Path | ForEach-Object {
        $line = $_.Trim()
        if ($line -eq "" -or $line.StartsWith("#")) { return }
        $idx = $line.IndexOf("=")
        if ($idx -lt 1) { return }
        $key = $line.Substring(0, $idx).Trim()
        $value = $line.Substring($idx + 1).Trim().Trim('"')
        $map[$key] = $value
    }
    return $map
}

$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$envPath = Join-Path $root ".env"
$envMap = Get-EnvMap -Path $envPath

$conn = $envMap["DB_CONNECTION"]
if ($conn -ne "mysql") {
    throw "This script currently supports DB_CONNECTION=mysql only."
}

$host = $envMap["DB_HOST"]
$port = $envMap["DB_PORT"]
$db = $envMap["DB_DATABASE"]
$user = $envMap["DB_USERNAME"]
$pass = $envMap["DB_PASSWORD"]

if (-not $host -or -not $db -or -not $user) {
    throw "Missing DB settings in server/.env (DB_HOST, DB_DATABASE, DB_USERNAME)."
}

$dumpExe = Get-Command mysqldump -ErrorAction SilentlyContinue
if (-not $dumpExe) {
    throw "mysqldump not found in PATH."
}

$backupDir = Join-Path $root "database/backups"
New-Item -ItemType Directory -Path $backupDir -Force | Out-Null

$stamp = Get-Date -Format "yyyyMMdd_HHmmss"
$outFile = Join-Path $backupDir "music_data_$stamp.sql"

$tables = @("genres", "artists", "tracks", "track_artists", "track_genres")
$args = @("-h", $host, "-P", $port, "-u", $user, "--single-transaction", "--quick", "--skip-lock-tables", $db) + $tables

if ($pass) {
    $env:MYSQL_PWD = $pass
}

try {
    & mysqldump @args | Out-File -FilePath $outFile -Encoding utf8
    Write-Host "Music data export created: $outFile"
}
finally {
    Remove-Item Env:MYSQL_PWD -ErrorAction SilentlyContinue
}

