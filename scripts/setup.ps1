# Rusz Dupę - Setup script for Windows
# Usage: .\scripts\setup.ps1

$ErrorActionPreference = "Stop"

Write-Host "==> Building Docker images..." -ForegroundColor Cyan
docker compose build

Write-Host "==> Installing backend dependencies (composer)..." -ForegroundColor Cyan
docker compose -f docker-compose.test.yml run --rm composer

Write-Host "==> Installing frontend dependencies (npm)..." -ForegroundColor Cyan
Push-Location frontend
try {
    npm ci
} finally {
    Pop-Location
}

Write-Host ""
Write-Host "Setup complete!" -ForegroundColor Green
Write-Host ""
Write-Host "Next steps:"
Write-Host "  npm run test:all    # Run all tests"
Write-Host "  npm run dev         # Start development stack"
