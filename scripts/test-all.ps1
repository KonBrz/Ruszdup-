# Rusz Dupę - Run all tests (Windows)
# Usage: .\scripts\test-all.ps1

$ErrorActionPreference = "Stop"

function Run-Step {
    param([string]$Name, [scriptblock]$Command)
    
    Write-Host ""
    Write-Host "==> $Name" -ForegroundColor Cyan
    & $Command
    if ($LASTEXITCODE -ne 0) {
        Write-Host "FAIL: $Name (exit code: $LASTEXITCODE)" -ForegroundColor Red
        exit $LASTEXITCODE
    }
    Write-Host "OK: $Name" -ForegroundColor Green
}

# Backend tests
Run-Step "Backend tests (PHPUnit in Docker)" {
    docker compose -f docker-compose.test.yml run --rm backend-test
}

# Frontend unit tests
Run-Step "Frontend unit tests (Vitest)" {
    Push-Location frontend
    try { npm run test:unit }
    finally { Pop-Location }
}

# E2E tests - check if stack is running
$frontendRunning = $false
try {
    $response = Invoke-WebRequest -Uri "http://localhost:5173" -TimeoutSec 2 -ErrorAction SilentlyContinue
    $frontendRunning = $response.StatusCode -eq 200
} catch {}

if (-not $frontendRunning) {
    Write-Host ""
    Write-Host "==> Starting dev stack for E2E..." -ForegroundColor Cyan
    docker compose up -d
    Write-Host "Waiting for services (30s)..." -ForegroundColor Yellow
    Start-Sleep -Seconds 30
}

Run-Step "E2E tests (Playwright)" {
    npx playwright test
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "ALL TESTS PASSED" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
