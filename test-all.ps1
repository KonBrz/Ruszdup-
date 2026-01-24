[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$ErrorActionPreference = "Stop"

$startTime = Get-Date
$startTimeIso = $startTime.ToUniversalTime().ToString("o")

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

function Get-ComposeLogs([string[]]$composeArgs) {
    $compose = Get-ComposeCommand
    if ($null -eq $compose) {
        return ""
    }
    $output = & $compose @composeArgs 2>&1
    return ($output | Out-String)
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

Run-Step "Frontend: unit tests (vitest, 8 tests)" {
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

$frontendLogs = Get-ComposeLogs @("logs", "--tail", "200", "frontend")
$frontendLogs = Get-ComposeLogs @("logs", "--since", $startTimeIso, "frontend")
$frontendConnRefusedCount = ([regex]::Matches($frontendLogs, "ECONNREFUSED 127\.0\.0\.1:8000")).Count

Write-Host ""
Write-Host "==> Frontend: e2e (playwright, 5 tests)"
Push-Location (Join-Path $root "frontend")
try { npm run test:e2e }
finally { Pop-Location }

$code = $LASTEXITCODE
if ($code -ne 0) {
    $resultsPath = Join-Path $root "frontend\test-results"
    if (Test-Path $resultsPath) {
        Get-ChildItem -Path $resultsPath -Recurse -Filter "error-context.md" | ForEach-Object {
            Write-Host ("E2E error context: " + $_.FullName) -ForegroundColor Yellow
        }
    }
    Write-Host "FAIL: Frontend: e2e (playwright, 5 tests) (exit code: $code)" -ForegroundColor Red
    exit $code
}
Write-Host "OK: Frontend: e2e (playwright, 5 tests)" -ForegroundColor Green

Write-Host ""
Write-Host "==> Log sanity check (since final-check start)"

$backendLogs = Get-ComposeLogs @("logs", "--since", $startTimeIso, "backend")
$backendErrorPattern = "(?i)Exception|ERROR|CRITICAL|Stack trace|SQLSTATE|Symfony\\\\Component\\\\ErrorHandler|Unhandled"
$backendMatches = [regex]::Matches($backendLogs, $backendErrorPattern)

if ($backendMatches.Count -gt 0) {
    Write-Host ("WARN: backend logs contain {0} error-like lines since start." -f $backendMatches.Count) -ForegroundColor Yellow
    $backendLines = $backendLogs -split "`r?`n"
    $matchedLines = $backendLines | Where-Object { $_ -match $backendErrorPattern }
    $matchedLines | Select-Object -First 30 | ForEach-Object { Write-Host $_ -ForegroundColor Yellow }
} else {
    Write-Host "OK: no backend log errors since start." -ForegroundColor Green
}

if ($frontendConnRefusedCount -gt 0) {
    Write-Host ("WARN: frontend logs show ECONNREFUSED 127.0.0.1:8000 {0} time(s) since start." -f $frontendConnRefusedCount) -ForegroundColor Yellow
} else {
    Write-Host "OK: no frontend proxy ECONNREFUSED since start." -ForegroundColor Green
}

Write-Host ""
Write-Host "ALL TESTS PASSED" -ForegroundColor Green
exit 0
