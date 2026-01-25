# Rusz Dupę - Clean all Docker resources (Windows)
# Usage: .\scripts\clean.ps1

Write-Host "==> Stopping and removing dev containers..." -ForegroundColor Cyan
docker compose down -v --remove-orphans

Write-Host "==> Stopping and removing test containers..." -ForegroundColor Cyan
docker compose -f docker-compose.test.yml down -v --remove-orphans

Write-Host ""
Write-Host "Cleanup complete!" -ForegroundColor Green
