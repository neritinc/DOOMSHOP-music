$ErrorActionPreference = "Stop"

function Require-Command {
    param([string]$Name)
    if (-not (Get-Command $Name -ErrorAction SilentlyContinue)) {
        throw "Missing required command: $Name"
    }
}

function Set-EnvValue {
    param(
        [string]$EnvPath,
        [string]$Key,
        [string]$Value
    )

    if (-not (Test-Path $EnvPath)) {
        return
    }

    $lines = Get-Content $EnvPath
    $pattern = "^\s*$([Regex]::Escape($Key))="
    $updated = $false

    for ($i = 0; $i -lt $lines.Count; $i++) {
        if ($lines[$i] -match $pattern) {
            $lines[$i] = "$Key=$Value"
            $updated = $true
            break
        }
    }

    if (-not $updated) {
        $lines += "$Key=$Value"
    }

    Set-Content -Path $EnvPath -Value $lines
}

Write-Host "Checking required tools..."
Require-Command php
Require-Command composer
Require-Command npm

$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$serverDir = Join-Path $root "server"
$clientDir = Join-Path $root "client"
$serverEnv = Join-Path $serverDir ".env"
$serverEnvExample = Join-Path $serverDir ".env.example"

Write-Host "Installing server PHP dependencies..."
Push-Location $serverDir
composer install

if (-not (Test-Path $serverEnv)) {
    Write-Host "Creating server .env from .env.example..."
    Copy-Item $serverEnvExample $serverEnv
}

Write-Host "Generating Laravel app key (if needed)..."
php artisan key:generate --force

Write-Host "Running migrations..."
php artisan migrate --force

Write-Host "Creating storage symlink..."
php artisan storage:link

Write-Host "Installing server JS dependencies..."
npm install
Pop-Location

Write-Host "Installing client dependencies..."
Push-Location $clientDir
npm install
Pop-Location

Write-Host "Detecting ffmpeg / ffprobe..."
function Find-Exe {
    param([string]$ExeName)
    $cmd = Get-Command $ExeName -ErrorAction SilentlyContinue | Select-Object -First 1 -ExpandProperty Source
    if ($cmd) { return $cmd }

    $wingetRoot = Join-Path $env:LOCALAPPDATA "Microsoft\\WinGet\\Packages"
    if (Test-Path $wingetRoot) {
        $found = Get-ChildItem -Path $wingetRoot -Recurse -Filter $ExeName -ErrorAction SilentlyContinue | Select-Object -First 1 -ExpandProperty FullName
        if ($found) { return $found }
    }

    return $null
}

$ffmpegPath = Find-Exe "ffmpeg.exe"
$ffprobePath = Find-Exe "ffprobe.exe"

if ($ffmpegPath -and $ffprobePath) {
    Write-Host "Saving ffmpeg paths into server .env..."
    Set-EnvValue -EnvPath $serverEnv -Key "FFMPEG_BINARIES" -Value $ffmpegPath
    Set-EnvValue -EnvPath $serverEnv -Key "FFPROBE_BINARIES" -Value $ffprobePath
} else {
    Write-Warning "ffmpeg/ffprobe not found in PATH. Install ffmpeg or set FFMPEG_BINARIES and FFPROBE_BINARIES in server/.env."
}

Write-Host ""
Write-Host "Setup completed."
Write-Host "Start backend:  cd server; composer run dev"
Write-Host "Start frontend: cd client; npm run dev"
