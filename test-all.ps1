[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$ErrorActionPreference = "Stop"

function Get-ComposeCommand {
    if (Get-Command docker-compose -ErrorAction SilentlyContinue) {
        return @("docker-compose")
    }
    if (Get-Command docker -ErrorAction SilentlyContinue) {
        return @("docker", "compose")
    }
    Write-Host "Docker Compose not found (docker-compose or docker compose)." -ForegroundColor Red
    $global:LASTEXITCODE = 1
    return $null
}

function Invoke-Compose([string[]]$composeArgs) {
    $compose = Get-ComposeCommand
    if ($null -eq $compose) {
        return 1
    }
    & $compose @composeArgs
    return $LASTEXITCODE
}

function Wait-Port([string]$targetHost, [int]$port, [int]$timeoutSec = 120) {
    $deadline = [DateTime]::UtcNow.AddSeconds($timeoutSec)
    while ([DateTime]::UtcNow -lt $deadline) {
        try {
            $client = New-Object System.Net.Sockets.TcpClient
            $async = $client.BeginConnect($targetHost, $port, $null, $null)
            if ($async.AsyncWaitHandle.WaitOne(1000, $false)) {
                $client.EndConnect($async) | Out-Null
                $client.Close()
                return $true
            }
            $client.Close()
        } catch {
            Start-Sleep -Seconds 1
        }
        Start-Sleep -Milliseconds 250
    }
    return $false
}

function Run-Step([string]$name, [scriptblock]$cmd) {
    Write-Host ""
    Write-Host "==> $name"
    & $cmd
    $code = $LASTEXITCODE
    if ($code -ne 0) {
        Write-Host "FAIL: $name (exit code: $code)" -ForegroundColor Red
        exit $code
    }
    Write-Host "OK: $name" -ForegroundColor Green
    return 0
}

$root = Split-Path -Parent $MyInvocation.MyCommand.Path

Run-Step "Backend: php artisan test" {
    Push-Location (Join-Path $root "backend")
    try { php artisan test }
    finally { Pop-Location }
}

Run-Step "Frontend: unit tests (vitest)" {
    Push-Location (Join-Path $root "frontend")
    try { npm run test:unit }
    finally { Pop-Location }
}

Run-Step "Docker: ensure stack is up" {
    Invoke-Compose @("up", "-d", "db", "backend", "frontend") | Out-Null
}

Write-Host "Waiting for backend on port 8000..." -ForegroundColor Cyan
if (-not (Wait-Port -targetHost "localhost" -port 8000 -timeoutSec 120)) {
    Write-Host "Backend port 8000 did not become ready." -ForegroundColor Red
    exit 1
}

Write-Host "Waiting for frontend on port 5173..." -ForegroundColor Cyan
if (-not (Wait-Port -targetHost "localhost" -port 5173 -timeoutSec 120)) {
    Write-Host "Frontend port 5173 did not become ready." -ForegroundColor Red
    exit 1
}

Run-Step "Frontend: e2e (playwright)" {
    Push-Location (Join-Path $root "frontend")
    try { npm run test:e2e }
    finally { Pop-Location }
}

Write-Host ""
Write-Host "ALL TESTS PASSED" -ForegroundColor Green
exit 0
